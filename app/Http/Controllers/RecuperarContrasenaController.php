<?php

namespace App\Http\Controllers;

use App\Models\BitacoraAuditoria;
use App\Models\CodigoRecuperacion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;

class RecuperarContrasenaController extends Controller
{
    private const MINUTOS_VIGENCIA = 10;
    private const MAX_INTENTOS = 5;
    private const SEGUNDOS_REENVIO = 60;

    //POST /password/olvide : { "email": "..." }
    //Responde siempre igual, exista o no la cuenta, para no revelar que correos estan registrados
    public function olvide(Request $request)
    {
        $datos = $request->validate(['email' => 'required|email|max:150']);

        $respuesta = response()->json([
            'mensaje' => 'Si la cuenta existe, te enviamos un codigo a tu correo',
        ], 200);

        $usuario = Usuario::where('correo', trim($datos['email']))->first();
        if (! $usuario) {
            return $respuesta;
        }

        //evita correos en rafaga
        $ultimo = CodigoRecuperacion::where('usuario_id', $usuario->id)->latest('created_at')->first();
        if ($ultimo && $ultimo->created_at->gt(now()->subSeconds(self::SEGUNDOS_REENVIO))) {
            return $respuesta;
        }

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        //un solo codigo vigente por usuario
        CodigoRecuperacion::where('usuario_id', $usuario->id)->delete();

        CodigoRecuperacion::create([
            'usuario_id'  => $usuario->id,
            'codigo_hash' => Hash::make($codigo),
            'expira_en'   => now()->addMinutes(self::MINUTOS_VIGENCIA),
            'intentos'    => 0,
        ]);

        try {
            Mail::raw(
                "Tu codigo para restablecer la contrasena de DAMA es: {$codigo}\n\n"
                . 'Vence en ' . self::MINUTOS_VIGENCIA . " minutos. Si no lo solicitaste, ignora este mensaje.",
                function ($mensaje) use ($usuario) {
                    $mensaje->to($usuario->correo)->subject('Recuperar contrasena - DAMA');
                }
            );
        } catch (\Throwable $e) {
            Log::warning('No se pudo enviar el codigo de recuperacion: ' . $e->getMessage());
        }

        return $respuesta;
    }

    //POST /password/restablecer : { email, codigo, password, password_confirmation }
    public function restablecer(Request $request)
    {
        $datos = $request->validate([
            'email'    => 'required|email|max:150',
            'codigo'   => 'required|digits:6',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $invalido = response()->json(['mensaje' => 'Codigo invalido o vencido. Solicita uno nuevo'], 422);

        $usuario = Usuario::where('correo', trim($datos['email']))->first();
        if (! $usuario) {
            return $invalido;
        }

        $registro = CodigoRecuperacion::where('usuario_id', $usuario->id)
            ->whereNull('usado_en')
            ->latest('created_at')
            ->first();

        if (! $registro || $registro->expira_en->isPast()) {
            return $invalido;
        }

        if ($registro->intentos >= self::MAX_INTENTOS) {
            return response()->json(['mensaje' => 'Demasiados intentos. Solicita un codigo nuevo'], 429);
        }

        if (! Hash::check($datos['codigo'], $registro->codigo_hash)) {
            $registro->increment('intentos');
            $restantes = max(0, self::MAX_INTENTOS - $registro->intentos);

            return response()->json(['mensaje' => "Codigo incorrecto. Te quedan {$restantes} intentos"], 422);
        }

        DB::transaction(function () use ($usuario, $registro, $datos) {
            $usuario->forceFill(['contrasena_hash' => Hash::make($datos['password'])])->save();
            $registro->forceFill(['usado_en' => now()])->save();
        });

        BitacoraAuditoria::registrar('actualizar', 'usuario', $usuario->id, $usuario->id);

        return response()->json(['mensaje' => 'Contrasena actualizada'], 200);
    }
}