<?php

namespace App\Services;

use App\Models\Notificacion;
use Illuminate\Support\Facades\Log;

class Notificador
{
    /**
     * Guarda la notificación en la bandeja y manda el push al terminar de
     * responder la petición. Nunca rompe la petición: si algo falla, queda en el log
     * con el usuario y el tipo para poder rastrearlo.
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
        } catch (\Throwable $e) {
            Log::error("Notificador: no se guardó la notificación (usuario {$usuarioId}, tipo '{$tipo}'): " . $e->getMessage());
            return null;
        }

        $carga = $datos + ['notificacion_id' => $notificacion->id, 'tipo' => $tipo];

        // El push va aparte: si falla, la notificación ya quedó en la bandeja
        app()->terminating(function () use ($usuarioId, $titulo, $cuerpo, $carga) {
            try {
                app(PushService::class)->enviarAUsuario($usuarioId, $titulo, $cuerpo, $carga);
            } catch (\Throwable $e) {
                Log::error("Notificador: falló el push al usuario {$usuarioId}: " . $e->getMessage());
            }
        });

        return $notificacion;
    }
}