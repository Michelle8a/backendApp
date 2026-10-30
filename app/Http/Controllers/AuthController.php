<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    //POST login
    public function login(Request $request)
    {
        $datos = $request->validate([
            'correo' => 'required|email',
            'contrasena' => 'required|string',
        ]);

        //busca el usuario por correo
        $usuario = Usuario::where('correo', $datos['correo'])->first();

        //mismo mensaje si falla el correo o la contraseña, para no dar pistas
        if (! $usuario || ! Hash::check($datos['contrasena'], $usuario->contrasena_hash)) {
            return response()->json(['mensaje' => 'Credenciales incorrectas'], 401);
        }

        //crea el token que la app va a mandar en cada peticion
        return response()->json([
            'token' => $usuario->createToken('app')->plainTextToken,
            'usuario' => $usuario,
        ], 200);
    }


    //GET perfil
    public function perfil(Request $request)
    {
        return response()->json($request->user(), 200);
    }

    //POST logout
    public function logout(Request $request)
    {
        //borra solo el token con el que se hizo esta peticion
        $request->user()->currentAccessToken()->delete();

        return response()->json(['mensaje' => 'Sesion cerrada'], 200);
    }
}
