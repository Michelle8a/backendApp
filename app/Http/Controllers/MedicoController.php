<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        $consulta = Medico::with('centro_salud');

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

    //POST: para crear un medico
    public function store(Request $request)
    {
        $usuario = $request->user();
        $esAdmin = $usuario->rol ==='admin';
        $esMedico = $usuario->rol === 'medico';

        $reglas = [
            'centro_salud_id' => 'required|exists:centros_salud,id',
            'jvpm' => 'required|string|max:100|unique:medicos,jvpm',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'es_general' => 'required|boolean',
            'biografia' => 'nullable|string|max:200',
            'tarifa' => 'required|numeric|min:0|max:999999.99',
        ];

        //el admin indica a que usuario (con rol medico) pertenece el perfil
        if($esAdmin){
            $reglas['usuario_id'] = [
                'required',
                Rule::exists('usuarios', 'id')->where('rol', 'medico'),
                'unique:medicos,usuario_id',
            ];
            $reglas['verificado'] = 'sometimes|boolean';
        }

        $datos = $request->validate($reglas);

        //el medico solo CREA SU perfil, el id sale del token
        if($esMedico){
            if(Medico::where('usuario_id', $usuario->id)->exists()){
                return response()->json(['mensaje' => 'Ya tienes un perfil de medico']);
            }
            $datos['usuario_id'] = $usuario->id;
        }

        //un medico nuevo queda pendiente hasta que el admin lo verifique
        $datos['verificado'] = $esAdmin ? ($datos['verificado'] ?? false) : false;

        //forceFill porque es_general aun no es $fillable del modelo
        $medico = new Medico();
        $medico->forceFill($datos)->save();

        BitacoraAuditoria::registrar('crear', 'medico', $medico->id);

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
        $this->autorizar($request, $medico);// Solo usuario propietario o admin

        $esAdmin = $request->user()->rol === 'admin';

        //campos que puede cambiar el usuario
        $reglas = [
            'centro_salud_id' => 'sometimes|exists:centros_salud,id',
            'nombres' => 'sometimes|string|max:100',
            'apellidos' => 'sometimes|string|max:100',
            'biografia' => 'sometimes|nullable|string|max:2000',
            'tarifa' => 'sometimes|numeric|min:0|max:999999.99'
        ];

        //campos que solo el adimin puede cambiar
        if($esAdmin){
            $reglas['jvpm'] = ['sometimes', 'string', 'max:100', Rule::unique('medicos', 'jvmp')->ignore($medico->id)];
            $reglas['es_general'] = 'sometimes|boolean';
            $reglas['verificado'] = 'sometimes|boolean';
        }

        //lo que no este en las reglas se ignora
        $datos = $request->validate($reglas);

        $medico->forceFill($datos)->save();

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

    //armar la respuesta: el centro siempre, los darois de la cuenta solo para usuario y admin
    private function formatear(Medico $medico, bool $verPrivado)
    {
        $medico->load('centro_salud');

        if($verPrivado){
            $medico->load('usuario:id,correo,telefono,rol');
        }

        return $medico;
    }
}
