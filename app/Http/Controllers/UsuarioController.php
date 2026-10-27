<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    //GET todos
    public function index()
    {
        return response()->json(Usuario::all(), 200);
    }

    //POST
    public function store(Request $request)
    {
        //validate checa que todo venga bien, sino no guarda nada
        $datos = $request->validate([
            'correo' => 'required|email|max:150|unique:usuarios,correo', //evita correos repetidos (tabla, columna)
            'contrasena' => 'required|string|min:8',
            'rol' => 'in:admin,paciente,medico',
            'telefono' => 'required|string|max:20',
            'consentimiento_aceptado' => 'required|boolean',
            'consentimiento_fecha' => 'required|date',
            'consentimiento_version' => 'required|string|max:10',
        ]);

        //hashea la contraseña 
        $datos['contrasena_hash'] = Hash::make($datos['contrasena']);
        unset($datos['contrasena']);
        
        //inserta la fila
        $usuario = Usuario::create($datos);
        $usuario->refresh(); //vuelve a leer la fila

        return response()->json($usuario, 201);
    }

    //GET uno
    public function show(Usuario $usuario)
    {
        return response()->json($usuario, 200);
    }

    //PUT
    public function update(Request $request, Usuario $usuario)
    {

        $datos = $request->validate([
            'correo' => [
                'sometimes', 'email', 'max:150',
                Rule::unique('usuarios', 'correo')->ignore($usuario->id), //evita correos repetidos ignorando al usuario actual
            ],
            'contrasena' => 'sometimes|string|min:8',
            'rol' => 'sometimes|in:admin,paciente,medico',
            'telefono' => 'sometimes|string|max:20',
            'consentimiento_aceptado' => 'sometimes|boolean',
            'consentimiento_fecha' => 'sometimes|date',
            'consentimiento_version' => 'sometimes|string|max:10',
        ]);

        //solo si mandaron una contraseña nueva
        if (isset($datos['contrasena'])) {
            $datos['contrasena_hash'] = Hash::make($datos['contrasena']);
            unset($datos['contrasena']);
        }
        
        //modifica la fila
        $usuario->update($datos);

        return response()->json($usuario, 200);
    }

    //DELETE
    public function destroy(Usuario $usuario)
    {
        $usuario->delete();

        return response()->json(null, 204);
    }
}
