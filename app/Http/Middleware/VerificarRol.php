<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

//Uso en rutas API:  ->middleware(['auth:api', 'rol:admin,medico'])
//Uso en rutas WEB:  ->middleware(['auth', 'rol:admin'])
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();
        $esApi = $request->expectsJson() || $request->is('api/*');

        //por si se olvida poner auth antes
        if (! $usuario) {
            return $esApi
                ? response()->json(['mensaje' => 'No autenticado'], 401)
                : redirect('/')->with('error', 'Debes iniciar sesión para continuar.');
        }

        //se compara con el rol de la BD (no con el claim)
        if (! in_array($usuario->rol, $roles, true)) {
            if ($esApi) {
                return response()->json([
                    'mensaje' => 'No tienes permiso para realizar esta accion',
                ], 403);
            }
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}
