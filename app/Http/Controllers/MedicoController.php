<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\BitacoraAuditoria;

class MedicoController extends Controller
{
    //GET: buscar medicos. Filtros opcionales
    public function index(Request $request)
    {
        $filtros = $request->validate([
            'q' => 'sometimes|string|max:100',
            'centro_salud_id' => 'sometimes|integer',
            'es_general' => 'sometimes|boolean',
            'verificado' => 'sometimes|boolean', //SOLO LO RESPETA EL ADMIN
        ]);

        $esAdmin = $request->user()->rol === 'admin';

        $consulta = Medico::with(['centro_salud', 'especialidades', 'idiomas']);

        if($esAdmin){
            $consulta->with('usuario:id,correo,telefono,rol');
            if(isset($filtros['verificado'])){
                $consulta->where('verificado', $filtros['verificado']);
            }
        }else{
            //paciente y medicos solo ven medicos verificados
            $consulta->where('verificado', true);
        }

        if(isset($filtros['centro_salud_id'])){
            $consulta->where('centro_salud_id', $filtros['centro_salud_id']);
        }

        if(isset($filtros['es_general'])){
            $consulta->where('es_general', $filtros['es_general']);
        }

        if(isset($filtros['q'])){
            $texto = $filtros['q'];
            $consulta->where(function ($w) use ($texto) {
                $w->where('nombres', 'like', "%{$texto}%")
                    ->orWhere('apellidos', 'like', "%{$texto}%");
            });
        }

        return response()->json($consulta->orderBy('apellidos')->get(), 200);
    }

    //GET: el medico del usuario que inicio sesion
    public function miPerfil(Request $request)
    {
        $medico = Medico::where('usuario_id', $request->user()->id)->first();

        if(! $medico){
            return response()->json(['mensaje' => 'Aun no has completado tu perfil de medico'], 404);
        }

        return response()->json($this->formatear($medico, true), 200);
    }

    //POST: registrar un medico. SOLO el admin (la ruta lo exige y aqui se vuelve a verificar)
    public function store(Request $request)
    {
        if($request->user()->rol !== 'admin'){
            abort(403, 'Solo un administrador puede registrar medicos');
        }

        $datos = $request->validate([
            //el usuario (con rol medico) al que pertenece el perfil
            'usuario_id' => [
                'required',
                Rule::exists('usuarios', 'id')->where('rol', 'medico'),
                'unique:medicos,usuario_id',
            ],
            'centro_salud_id' => 'required|exists:centros_salud,id',
            'jvpm' => 'required|string|max:100|unique:medicos,jvpm',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'es_general' => 'required|boolean',
            'biografia' => 'nullable|string|max:200',
            'tarifa' => 'required|numeric|min:0|max:999999.99',
            'verificado' => 'sometimes|boolean',
            'especialidades' => 'sometimes|array',
            'especialidades.*' => 'integer|exists:especialidades,id',
            'idiomas' => 'sometimes|array',
            'idiomas.*' => 'integer|exists:idiomas,id',
        ]);

        //las relaciones se guardan aparte, no son columnas de medicos
        $especialidades = $datos['especialidades'] ?? null;
        $idiomas = $datos['idiomas'] ?? null;
        unset($datos['especialidades'], $datos['idiomas']);

        $datos['verificado'] = $datos['verificado'] ?? false;

        //forceFill por si es_general aun no es $fillable del modelo
        $medico = new Medico();
        $medico->forceFill($datos)->save();

        if($especialidades !== null) $medico->especialidades()->sync($especialidades);
        if($idiomas !== null) $medico->idiomas()->sync($idiomas);

        BitacoraAuditoria::registrar('crear', 'medico', $medico->id);

        return response()->json($this->formatear($medico, true), 201);
    }

    //POST: registrar un medico junto con su cuenta de usuario. SOLO ADMIN
    public function registrarConUsuario(Request $request)
    {
        if($request->user()->rol !== 'admin'){
            abort(403, 'Solo un administrador puede registrar medicos');
        }

        $datos = $request->validate([
            'correo' => 'required|email|max:120|unique:usuarios,correo',
            'contrasena' => 'required|string|min:8|max:64',
            'telefono' => 'nullable|string|max:20',
            'centro_salud_id' => 'required|exists:centros_salud,id',
            'jvpm' => 'required|string|max:100|unique:medicos,jvpm',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'es_general' => 'required|boolean',
            'biografia' => 'nullable|string|max:200',
            'tarifa' => 'required|numeric|min:0|max:999999.99',
            'verificado' => 'sometimes|boolean',
            'especialidades' => 'sometimes|array',
            'especialidades.*' => 'integer|exists:especialidades,id',
            'idiomas' => 'sometimes|array',
            'idiomas.*' => 'integer|exists:idiomas,id',
        ]);

        $medico = DB::transaction(function () use ($datos){
            //1. la cuenta
            $usuario = new Usuario();
            $usuario->forceFill([
                'correo' => $datos['correo'],
                'contrasena_hash' => Hash::make($datos['contrasena']),
                'telefono' => $datos['telefono'] ?? null,
                'rol' => 'medico',
                'estado' => 'activo',
                'consentimiento_aceptado' => 1,
                'consentimiento_version' => '1.0',
                'consentimiento_fecha' => now(),
            ])->save();

            //2. el perfil del medico
            $medico = new Medico();
            $medico->forceFill([
                                'usuario_id' => $usuario->id,
                'centro_salud_id' => $datos['centro_salud_id'],
                'jvpm' => $datos['jvpm'],
                'nombres' => $datos['nombres'],
                'apellidos' => $datos['apellidos'],
                'es_general' => $datos['es_general'],
                'biografia' => $datos['biografia'] ?? null,
                'tarifa' => $datos['tarifa'],
                'verificado' => $datos['verificado'] ?? true,
            ])->save();

            $medico->especialidades()->sync($datos['especialidades'] ?? []);
            $medico->idiomas()->sync($datos['idiomas'] ?? []);

            return $medico;
        });

        bitacoraAuditoria::registrar('crear', 'medico', $medico->id);

        return response()->json($this->formatear($medico, true), 201);
    }

    //GET: uno
    public function show(Request $request, Medico $medico)
    {
        $verPrivado = $this->esDuenoOAdmin($request, $medico);

        //un medico sin verificar solo lo ve el usuario y el admin
        if(! $verPrivado && ! $medico->verificado){
            abort(404, 'Medico no encontrado');
        }

        return response()->json($this->formatear($medico, $verPrivado), 200);
    }

    //PUT
    public function update(Request $request, Medico $medico)
    {
        $this->autorizar($request, $medico); // Solo usuario propietario o admin

        $esAdmin = $request->user()->rol === 'admin';

        //campos que puede cambiar el usuario
        $reglas = [
            'centro_salud_id' => 'sometimes|exists:centros_salud,id',
            'nombres' => 'sometimes|string|max:100',
            'apellidos' => 'sometimes|string|max:100',
            'biografia' => 'sometimes|nullable|string|max:200',
            'tarifa' => 'sometimes|numeric|min:0|max:999999.99',
            'especialidades' => 'sometimes|array',
            'especialidades.*' => 'integer|exists:especialidades,id',
            'idiomas' => 'sometimes|array',
            'idiomas.*' => 'integer|exists:idiomas,id',
        ];

        //campos que solo el admin puede cambiar
        if($esAdmin){
            $reglas['jvpm'] = ['sometimes', 'string', 'max:100', Rule::unique('medicos', 'jvpm')->ignore($medico->id)];
            $reglas['es_general'] = 'sometimes|boolean';
            $reglas['verificado'] = 'sometimes|boolean';
        }

        //lo que no este en las reglas se ignora
        $datos = $request->validate($reglas);

        $especialidades = $datos['especialidades'] ?? null;
        $idiomas = $datos['idiomas'] ?? null;
        unset($datos['especialidades'], $datos['idiomas']);

        $medico->forceFill($datos)->save();

        if($especialidades !== null) $medico->especialidades()->sync($especialidades);
        if($idiomas !== null) $medico->idiomas()->sync($idiomas);

        BitacoraAuditoria::registrar('actualizar', 'medico', $medico->id);

        return response()->json($this->formatear($medico, true), 200);
    }

    //PATCH: para verificar o quitar la verificacion (Solo admin, lo exige la ruta)
    public function verificar(Request $request, Medico $medico)
    {
        $datos = $request->validate([
            'verificado' => 'required|boolean',
        ]);

        $medico->forceFill($datos)->save();

        return response()->json($this->formatear($medico, true), 200);
    }

    //DELETE solo admin
    public function destroy(Request $request, Medico $medico)
    {
        if($request->user()->rol !== 'admin'){
            abort(403, 'solo un administrador puede eliminar medicos');
        }

        $medico->delete();
        BitacoraAuditoria::registrar('eliminar', 'medico', $medico->id);

        return response()->json(null, 204);
    }

    //---------------- AUXILIARES ----------------

    private function esDuenoOAdmin(Request $request, Medico $medico): bool
    {
        $usuario = $request->user();

        return $usuario->rol === 'admin'
            || (int) $medico->usuario_id === (int) $usuario->id;
    }

    private function autorizar(Request $request, Medico $medico): void
    {
        if(! $this->esDuenoOAdmin($request, $medico)){
            abort(403, 'No tienes permiso para modificar este medico');
        }
    }

    //armar la respuesta: el centro, especialidades e idiomas siempre; los datos de la cuenta solo para usuario y admin
    private function formatear(Medico $medico, bool $verPrivado)
    {
        $medico->load(['centro_salud', 'especialidades', 'idiomas']);

        if($verPrivado){
            $medico->load('usuario:id,correo,telefono,rol');
        }

        return $medico;
    }
}