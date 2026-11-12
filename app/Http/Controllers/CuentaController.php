<?php

namespace App\Http\Controllers;

use App\Models\BitacoraAuditoria;
use App\Models\CodigoVerificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CuentaController extends Controller
{
    //PUT /auth/correo : { "correo": "nuevo@correo.com", "contrasena": "la actual" }
    public function cambiarCorreo(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'correo' => [
                'required', 'email', 'max:120',
                Rule::unique('usuarios', 'correo')->ignore($usuario->id),
            ],
            'contrasena' => 'required|string',
        ]);

        //confirmar identidad: sin la contraseña actual no se cambia el correo
        if (! Hash::check($datos['contrasena'], $usuario->contrasena_hash)) {
            return response()->json([
                'message' => 'La contrasena es incorrecta',
                'errors'  => ['contrasena' => ['La contrasena es incorrecta']],
            ], 422);
        }

        $nuevo = mb_strtolower(trim($datos['correo']));

        if ($nuevo === mb_strtolower($usuario->correo)) {
            return response()->json([
                'message' => 'El nuevo correo es igual al actual',
                'errors'  => ['correo' => ['El nuevo correo es igual al actual']],
            ], 422);
        }

        DB::transaction(function () use ($usuario, $nuevo) {
            //el correo nuevo vuelve a quedar sin verificar y se descartan codigos viejos
            $usuario->forceFill([
                'correo'               => $nuevo,
                'correo_verificado_en' => null,
            ])->save();

            CodigoVerificacion::where('usuario_id', $usuario->id)->delete();
        });

        BitacoraAuditoria::registrar('actualizar', 'usuario', $usuario->id);

        return response()->json([
            'mensaje' => 'Correo actualizado. Verifica tu nuevo correo',
            'usuario' => $usuario->only(['id', 'correo', 'telefono', 'rol', 'correo_verificado_en']),
        ], 200);
    }
}