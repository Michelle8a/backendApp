<?php

namespace App\Http\Controllers;

use App\Models\TokenDispositivo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TokenDispositivoController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'token_fcm'  => ['required', 'string', 'max:255'],
            'plataforma' => ['required', 'in:android,ios'],
        ]);

        $token = TokenDispositivo::updateOrCreate(
            ['token_fcm' => $datos['token_fcm']],
            [
                'usuario_id' => $request->user()->id, // sale del JWT, nunca del body
                'plataforma' => $datos['plataforma'],
            ]
        );

        $creado = $token->wasRecentlyCreated;

        return response()->json([
            'mensaje' => $creado ? 'Dispositivo registrado.' : 'Dispositivo actualizado.',
            'data'    => $token,
        ], $creado ? 201 : 200);
    }

    public function destroy(Request $request): Response
    {
        $datos = $request->validate([
            'token_fcm' => ['required', 'string', 'max:255'],
        ]);

        TokenDispositivo::where('token_fcm', $datos['token_fcm'])
            ->where('usuario_id', $request->user()->id)
            ->delete();

        return response()->noContent();
    }
}