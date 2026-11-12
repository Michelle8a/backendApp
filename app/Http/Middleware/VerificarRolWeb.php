<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

//Version WEB (sesion) de VerificarRol. La original responde JSON para la API/JWT,
//esta redirige al login o muestra 403 para el panel del Admin.
//Uso en rutas:  ->middleware(['auth', 'rol.web:admin'])
class VerificarRolWeb
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return redirect()->route('login');
        }

        //se compara contra la BD, igual que en la version API
        if (! in_array($usuario->rol, $roles, true)) {
            abort(403, 'No tienes permiso para acceder a esta seccion');
        }

        return $next($request);
    }
}
