<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class Correo
{
    /**
     * Envia un correo de texto plano.
     * Si hay CORREO_WEBHOOK_URL usa el puente HTTPS (Render gratis bloquea SMTP);
     * si no, usa el mailer normal de Laravel.
     */
    public static function enviar(string $para, string $asunto, string $mensaje): void
    {
        $url = config('services.correo_web.url');

        if (! $url) {
            Mail::raw($mensaje, function ($m) use ($para, $asunto) {
                $m->to($para)->subject($asunto);
            });
            return;
        }

        $respuesta = Http::timeout(20)->asJson()->post($url, [
            'token'   => config('services.correo_web.token'),
            'para'    => $para,
            'asunto'  => $asunto,
            'mensaje' => $mensaje,
        ]);

        if (! $respuesta->successful() || trim($respuesta->body()) !== 'ok') {
            throw new \RuntimeException('El puente de correo respondio: ' . substr($respuesta->body(), 0, 200));
        }
    }
}