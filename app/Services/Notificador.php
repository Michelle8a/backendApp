<?php

namespace App\Services;

use App\Models\Notificacion;
use Illuminate\Support\Facades\Log;

class Notificador
{
    /**
     * Guarda la notificación en la bandeja y manda el push al terminar de
     * responder la petición. Si algo falla, solo se registra en el log.
     */
    public static function notificar(
        int $usuarioId,
        string $tipo,
        string $titulo,
        string $cuerpo,
        array $datos = [],
        ?int $citaId = null
    ): ?Notificacion {
        try {
            $notificacion = Notificacion::create([
                'usuario_id'      => $usuarioId,
                'cita_id'         => $citaId,
                'tipo'            => $tipo,
                'titulo'          => $titulo,
                'cuerpo'          => $cuerpo,
                'datos'           => $datos ?: null,
                'leida'           => false,
                'programada_para' => null,
                'enviada_en'      => now(),
            ]);

            $carga = $datos + ['notificacion_id' => $notificacion->id, 'tipo' => $tipo];

            app()->terminating(function () use ($usuarioId, $titulo, $cuerpo, $carga) {
                app(PushService::class)->enviarAUsuario($usuarioId, $titulo, $cuerpo, $carga);
            });

            return $notificacion;
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la notificación: ' . $e->getMessage());
            return null;
        }
    }
}