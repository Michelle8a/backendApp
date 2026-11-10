<?php

namespace App\Http\Controllers;

use App\Models\CodigoVerificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class CodigoVerificacionController extends Controller
{
    private const MINUTOS_VIGENCIA = 10;   //el codigo expira a los 10 minutos
    private const MAX_INTENTOS = 5;        //intentos fallidos antes de invalidarlo
    private const SEGUNDOS_REENVIO = 60;   //espera minima entre envios

    //POST /auth/verificacion/enviar : genera y envia un codigo de 6 digitos
    public function enviar(Request $request)
    {
        $usuario = $request->user();

        if ($usuario->correo_verificado_en) {
            return response()->json(['mensaje' => 'Tu correo ya esta verificado'], 409);
        }

        //evita que alguien dispare correos en rafaga
        $ultimo = CodigoVerificacion::where('usuario_id', $usuario->id)->latest('created_at')->first();
        if ($ultimo && $ultimo->created_at->gt(now()->subSeconds(self::SEGUNDOS_REENVIO))) {
            $espera = self::SEGUNDOS_REENVIO - $ultimo->created_at->diffInSeconds(now());
            return response()->json([
                'mensaje' => "Espera {$espera} segundos antes de pedir otro codigo",
            ], 429);
        }

        //random_int es criptograficamente seguro; str_pad conserva los ceros a la izquierda
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        //un solo codigo vigente por usuario: se descartan los anteriores
        CodigoVerificacion::where('usuario_id', $usuario->id)->delete();

        CodigoVerificacion::create([
            'usuario_id'  => $usuario->id,
            'codigo_hash' => Hash::make($codigo),   //nunca se guarda el codigo en claro
            'expira_en'   => now()->addMinutes(self::MINUTOS_VIGENCIA),
            'intentos'    => 0,
        ]);

        Mail::raw(
            "Tu codigo de verificacion de DAMA es: {$codigo}\n\n"
            . 'Vence en ' . self::MINUTOS_VIGENCIA . " minutos. Si no lo solicitaste, ignora este mensaje.",
            function ($mensaje) use ($usuario) {
                $mensaje->to($usuario->correo)->subject('Codigo de verificacion - DAMA');
            }
        );

        return response()->json([
            'mensaje'          => 'Te enviamos un codigo a tu correo',
            'vigencia_minutos' => self::MINUTOS_VIGENCIA,
        ], 200);
    }

    //POST /auth/verificacion/verificar : { "codigo": "123456" }
    public function verificar(Request $request)
    {
        $datos = $request->validate([
            'codigo' => 'required|digits:6',
        ]);

        $usuario = $request->user();

        if ($usuario->correo_verificado_en) {
            return response()->json(['mensaje' => 'Tu correo ya esta verificado'], 409);
        }

        $registro = CodigoVerificacion::where('usuario_id', $usuario->id)
            ->whereNull('usado_en')
            ->latest('created_at')
            ->first();

        if (! $registro) {
            return response()->json(['mensaje' => 'No hay un codigo activo. Solicita uno nuevo'], 422);
        }

        if ($registro->expira_en->isPast()) {
            return response()->json(['mensaje' => 'El codigo expiro. Solicita uno nuevo'], 422);
        }

        if ($registro->intentos >= self::MAX_INTENTOS) {
            return response()->json(['mensaje' => 'Demasiados intentos. Solicita un codigo nuevo'], 429);
        }

        if (! Hash::check($datos['codigo'], $registro->codigo_hash)) {
            $registro->increment('intentos');
            $restantes = max(0, self::MAX_INTENTOS - $registro->intentos);

            return response()->json([
                'mensaje' => "Codigo incorrecto. Te quedan {$restantes} intentos",
            ], 422);
        }

        //correcto: se marca el codigo como usado y el correo como verificado, todo junto
        DB::transaction(function () use ($registro, $usuario) {
            $registro->forceFill(['usado_en' => now()])->save();
            $usuario->forceFill(['correo_verificado_en' => now()])->save();
        });

        return response()->json([
            'mensaje' => 'Correo verificado correctamente',
            'usuario' => $usuario->only(['id', 'correo', 'telefono', 'rol', 'correo_verificado_en']),
        ], 200);
    }
}