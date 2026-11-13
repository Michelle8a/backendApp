<?php

namespace App\Services;

use App\Models\Notificacion;
use App\Models\TokenDispositivo;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushService
{
    private const URL  = 'https://exp.host/--/api/v2/push/send';
    private const LOTE = 100; // máximo que acepta Expo por petición

    /**
     * Manda un push a todos los dispositivos del usuario.
     * Nunca lanza excepciones: un fallo de push no debe romper la operación principal.
     */
    public function enviarAUsuario(int $usuarioId, string $titulo, string $cuerpo, array $datos = []): void
    {
        $tokens = TokenDispositivo::where('usuario_id', $usuarioId)
            ->pluck('token_fcm')
            ->filter(fn ($t) => str_starts_with($t, 'ExponentPushToken[') || str_starts_with($t, 'ExpoPushToken['))
            ->values();

        if ($tokens->isEmpty()) {
            return;
        }

        $noLeidas = Notificacion::where('usuario_id', $usuarioId)
            ->whereNotNull('enviada_en')
            ->where('leida', false)
            ->count();

        foreach ($tokens->chunk(self::LOTE) as $lote) {
            $lote = $lote->values()->all();

            $mensajes = array_map(fn ($token) => [
                'to'       => $token,
                'title'    => $titulo,
                'body'     => $cuerpo,
                'data'     => (object) $datos,
                'sound'    => 'default',
                'priority' => 'high',
                'badge'    => $noLeidas,
            ], $lote);

            try {
                $respuesta = Http::timeout(8)->acceptJson()->asJson()->post(self::URL, $mensajes);

                if (! $respuesta->successful()) {
                    Log::warning('Expo push: respuesta ' . $respuesta->status() . ' ' . $respuesta->body());
                    continue;
                }

                $this->limpiarTokensInvalidos($lote, $respuesta->json('data') ?? []);
            } catch (\Throwable $e) {
                Log::warning('Expo push falló: ' . $e->getMessage());
            }
        }
    }

    /** Expo avisa cuando un token ya no sirve (app desinstalada, etc.): se borra. */
    private function limpiarTokensInvalidos(array $tokens, array $tickets): void
    {
        foreach ($tickets as $i => $ticket) {
            $error = $ticket['details']['error'] ?? null;

            if (($ticket['status'] ?? '') === 'error' && $error === 'DeviceNotRegistered' && isset($tokens[$i])) {
                TokenDispositivo::where('token_fcm', $tokens[$i])->delete();
            }
        }
    }
}