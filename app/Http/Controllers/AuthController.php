<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class AuthController extends Controller
{
    //POST /api/auth/register (PUBLICO SOLO PARA CREAR PACIENTES)
    public function register (Request $request)
    {
        $datos = $request->validate([
            'correo' => 'required|email|max:150|unique:usuarios,correo',
            'contrasena' => 'required|string|min:8',
            'telefono' => 'required|string|max:20',
            'consentimiento_aceptado' => 'required|accepted',
            'consentimiento_version' => 'required|string|max:10',
        ]);

        //EL ROL NO VIENE DEL CLIENTE, VIENE DE CUALQUIERA QUE SEA EL ADMIN
        $usuario = Usuario::create([
            'correo' => $datos['correo'],
            'contrasena_hash' => Hash::make($datos['contrasena']),
            'rol' => 'paciente',
            'telefono' => $datos['telefono'],
            'consentimiento_aceptado' => true,
            'consentimiento_fecha' => now(),
            'consentimiento_version' => $datos['consentimiento_version'],
        ]);

        $token = Auth::guard('api')->login($usuario);

        return $this->respuestaToken($token, $usuario, 201);
    }

    //POST login
    public function login(Request $request)
    {
        $request->validate([
            'correo'=> 'required|email',
            'contrasena' => 'required|string',
        ]);

        //TTEMPT() ESPERA LA CLAVE "password" AUNQUE LA COLUMNA SE LLAME "contrasena_hash"
        //(getAuthPassword() del modelo hace el puente)
        $token = Auth::guard('api')->attempt([
            'correo' => $request->correo,
            'password' => $request->contrasena,
        ]);

        if(! $token){
            return response()->json(['mensaje' => 'Credenciales incorrectas'], 401);
        }

        return $this->respuestaToken($token, Auth::guard('api')->user());
    }


    //GET api/auth/perfil
    public function perfil()
    {
        return response()->json(Auth::guard('api')->user(), 200);
    }

    //POST /api/auth/refresh
    public function refresh()
    {
        $token = Auth::guard('api')->refresh();

        return $this->respuestaToken($token, Auth::guard('api')->user());
    }

    //POST logout (invalida el token)
    public function logout(Request $request)
    {
        Auth::guard('api')->logout();

        return response()->json(['mensaje' => 'Sesion cerrada'], 200);
    }

    private function respuestaToken(string $token, Usuario $usuario, int $codigo = 200)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expire_in' => Auth::guard('api')->factory()->getTTL() * 60, //segundos
            'usuario' => $usuario,
        ], $codigo);
    }
}
