<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use App\Models\BitacoraAuditoria;


class PacienteController extends Controller
{
    //GET: Solo admin y medico, lo controla la ruta
    public function index()
    {
        $pacientes = Paciente::with(['usuario:id,correo,telefono,rol', 'idioma'])
            ->get()
            ->makeHidden('dui_cifrado');

        return response()->json($pacientes, 200);
    }

    //GET: el paciente del usuario que inicio sesion
    public function miPerfil(Request $request)
    {
        $paciente = Paciente::where('usuario_id', $request->user()->id)->first();

        if(! $paciente){
            return response()->json(['mensaje' => 'Aun no completas tu perfil de paciente']);
        }

        return response()->json($this->formatear($paciente, true), 200);
    }

    //POST
    public function store(Request $request)
    {
        $usuario = $request->user();
        $esAdmin = $usuario->rol === 'admin';
        $esPaciente = $usuario->rol === 'paciente';

        $reglas = [
            'idioma_id' => 'required|exists:idiomas,id',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'dui' => ['required', 'string', 'regex:/^\d{8}-\d$/'], //formato 00000000-0
            'fecha_nacimiento' => 'required|date|before:today',
            'sexo' => 'required|in:M,F,Otro',
            'tipo_sangre' => 'required|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'direccion' => 'required|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ];

        //El admin indica que usuario (con rol paciente) pertenece el perfil
        if($esAdmin){
            $reglas['usuario_id'] = [
                'required',
                Rule::exists('usuarios', 'id')->where('rol', 'paciente'),
                'unique:pacientes,usuario_id',
            ];
        }



        $datos = $request->validate($reglas);

         //el paciente solo puede CREAR su perfil, el id lo trae el claim del token
         if($esPaciente){
            if(Paciente::where('usuario_id', $usuario->id)->exists()){
                return response()->json(['mensaje' => 'ya tienes un perfil de paciente'], 409);
            }
            $datos['usuario_id'] = $usuario->id;
         }

         //el DUI se guarda cifrado, nunca en texto plano
         $datos['dui_cifrado'] = Crypt::encryptString($datos['dui']);
         unset($datos['dui']);

         $paciente = Paciente::create($datos);
         BitacoraAuditoria::registrar('crear', 'paciente', $paciente->id);

         return response()->json($this->formatear($paciente, true), 201);
    }

    //GET uno
    public function show(Request $request, Paciente $paciente)
    {
        $this->autorizar($request, $paciente, true); //rol de medico tambien puede ver

        $verDui = $this->esDuenoOAdmin($request, $paciente);

        return response()->json($this->formatear($paciente, $verDui), 200);
    }

    //PUT
    public function update(Request $request, Paciente $paciente)
    {
        $this->autorizar($request, $paciente); // solo el perfil dueño o admin puede modificar

        //no se incluye usuario_id porque un paciente no cambia de usuario
        $datos = $request->validate([
            'idioma_id' => 'sometimes|exists:idiomas,id',
            'nombres' => 'sometimes|string|max:100',
            'apellidos' => 'sometimes|string|max:100',
            'dui' => ['sometimes', 'string', 'regex:/^\d{8}-\d$/'],
            'fecha_nacimiento' => 'sometimes|date|before:today',
            'sexo' => 'sometimes|in:M,F,otro',
            'tipo_sangre' => 'sometimes|string|max:3',
            'direccion' => 'sometimes|string|max:255',
            'latitud' => 'sometimes|numeric|between:-90,90',
            'longitud' => 'sometimes|numeric|between:-180,180',
        ]);

        if(isset($datos['dui'])){
            $datos['dui_cifrado'] = Crypt::encryptString($datos['dui']);
            unset($datos['dui']);
        }

        $paciente->update($datos);

        BitacoraAuditoria::registrar('actualizar', 'paciente', $paciente->id);

        return response()->json($this->formatear($paciente, true), 200);
    }

    //DELETE (solo admin: la ruta lo exige y aqui se vuelve a verificar)
    public function destroy(Paciente $paciente)
    {
        if($request->user()->rol !== 'admin'){
            abort(403, 'Solo un administrador puede eliminar pacientes');
        }

        $paciente->delete();
        BitacoraAuditoria::registrar('eliminar', 'paciente', $paciente->id);

        return response()->json(null, 204);
    }

    //---------------- HELPER ----------------

    private function esDuenoOAdmin(Request $request, Paciente $paciente): bool
    {
        $usuario = $request->user();

        return $usuario->rol === 'admin' || (int) $paciente->usuario_id === (int) $usuario->id;
    }

    //corta con un 403 si el usuario no puede tocar otro paciente
    private function autorizar(Request $request, Paciente $paciente, bool $permitirMedico = false): void
    {
        if($permitirMedico && $request->user()->rol === 'medico'){
            return;
        }

        if(! $this->esDuenoOAdmin($request, $paciente)){
            abort(403, 'No tienes permiso para acceder a este paciente');
        }
    }

    //arma las respuestas: relaciones, DUI descifrado solo si corresponde
    private function formatear(Paciente $paciente, bool $conDui)
    {
        $paciente->load(['usuario:id,correo,telefono,rol', 'idioma']);

        if($conDui){
            try{
                $paciente->setAttribute('dui', Crypt::decryptString($paciente->dui_cifrado));
            }catch(\Throwable $e){
                $paciente->setAttribute('dui', null); //dato viejo que no estaba cifrado 
            }
        }

        return $paciente->makeHidden('dui_cifrado');
    }
}