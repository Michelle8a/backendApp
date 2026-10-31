<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

//USO EN RUTAS: ->middleware(['auth:api', 'rol:admin,medico'])
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if(! $usuario){
            return response()->json(['mensaje' => 'No autenticado'], 401);
        }

        //Se compara con el rol de la base de datos (nunca con el claim), asi un cambio de rol
        //surte efecto aunque el token siga vigente
        if(! in_array($usuario->rol, $roles, true)){
            return response()->json([
                'mensaje' => 'No tienes permiso para realizar esta accion.'
            ], 403);
        }

        return $next($request);
    }
}