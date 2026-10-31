<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //alias para usar -> middleware('rol:admin,medico') en las rutas
        $middleware->alias([
            'rol' => \App\Http\Middleware\VerificarRol::class,
        ]);

        //API sin pantalla de login: no redirige DE MOMENTO, responde un 401
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //en /api/* siempre responder JSON, aunque postman no mande Accept: application/json
        $exceptions->shouldRenderJsonWhen(function ($request, $e){
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
