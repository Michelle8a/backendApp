<?php

namespace App\Http\Controllers;

use App\Models\Conversacion;
use App\Models\Mensaje;
use App\Models\BitacoraAuditoria;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Notificacion;
use App\Services\Notificador;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class MensajeController extends Controller
{
    //---------HELPERS----------

    private function esParticipante(Conversacion $conversacion, int $usuarioId): bool
    {
        return $conversacion->participantes()->where('usuarios.id', $usuarioId)->exists();
    }

    /** Nombre para mostrar en el aviso (médico o paciente; si no, "Nuevo mensaje"). */
    private function nombreDe(int $usuarioId): string
    {
        $persona = Medico::where('usuario_id', $usuarioId)->first()
            ?? Paciente::where('usuario_id', $usuarioId)->first();

        $nombre = $persona ? trim(($persona->nombres ?? '') . ' ' . ($persona->apellidos ?? '')) : '';

        return $nombre !== '' ? $nombre : 'Nuevo mensaje';
    }

    /** Avisa a los demás participantes. Nunca debe romper el envío del mensaje. */
    private function avisarMensaje(Conversacion $conversacion, int $emisorId, array $datos): void
    {
        try {
            $titulo = $this->nombreDe($emisorId);
            $cuerpo = $datos['tipo'] === 'texto'
                ? Str::limit($datos['contenido'], 80)
                : ($datos['tipo'] === 'imagen' ? '📷 Imagen' : '📎 Archivo');

            $destinatarios = $conversacion->participantes()
                ->where('usuarios.id', '!=', $emisorId)
                ->pluck('usuarios.id');

            foreach ($destinatarios as $uid) {
                // un solo aviso sin leer por conversación
                Notificacion::where('usuario_id', $uid)
                    ->where('tipo', 'mensaje')
                    ->where('leida', false)
                    ->where('datos->conversacion_id', $conversacion->id)
                    ->delete();

                Notificador::notificar(
                    $uid,
                    'mensaje',
                    $titulo,
                    $cuerpo,
                    ['pantalla' => 'chat', 'conversacion_id' => $conversacion->id]
                );
            }
        } catch (\Throwable $e) {
            Log::warning('No se pudo avisar del mensaje: ' . $e->getMessage());
        }
    }

    /** Al leer el chat, el aviso de esa conversación también queda leído. */
    private function marcarAvisoLeido(int $usuarioId, int $conversacionId): void
    {
        Notificacion::where('usuario_id', $usuarioId)
            ->where('tipo', 'mensaje')
            ->where('leida', false)
            ->where('datos->conversacion_id', $conversacionId)
            ->update(['leida' => true]);
    }

    /**
     * El contenido se guarda cifrado; hacia la app siempre sale descifrado
     * y nunca se expone la columna contenido_cifrado.
     */
    private function formatear(Mensaje $m): array
    {
        try {
            $contenido = Crypt::decryptString($m->contenido_cifrado);
        } catch (DecryptException $e) {
            $contenido = null;
        }

        return [
            'id'              => $m->id,
            'conversacion_id' => $m->conversacion_id,
            'emisor_id'       => $m->emisor_id,
            'tipo'            => $m->tipo,
            'contenido'       => $contenido,
            'adjunto_url'     => $m->adjunto_url,
            'leido'           => (bool) $m->leido,
            'enviado_en'      => $m->enviado_en,
        ];
    }

    //---------ENDPOINTS----------

    /**
     * GET /conversaciones/{conversacion}/mensajes?antes_de=120&per_page=30
     * Leer siempre se permite (los chats cerrados quedan en solo lectura).
     */
    public function index(Request $request, Conversacion $conversacion)
    {
        if (! $this->esParticipante($conversacion, $request->user()->id)) {
            return response()->json(['mensaje' => 'No participas en esta conversacion'], 403);
        }

        $request->validate([
            'antes_de' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $limite = (int) $request->input('per_page', 30);

        $query = $conversacion->mensajes()->orderByDesc('id');

        if ($request->filled('antes_de')) {
            $query->where('id', '<', $request->antes_de);
        }

        $mensajes = $query->limit($limite + 1)->get();
        $hayMas = $mensajes->count() > $limite;

        return response()->json([
            'data'    => $mensajes->take($limite)->map(fn ($m) => $this->formatear($m))->values(),
            'hay_mas' => $hayMas,
        ]);
    }

    /**
     * POST /conversaciones/{conversacion}/mensajes
     * paciente y medico, solo si participan en la conversacion Y el chat de la cita esta abierto.
     * El tipo 'bot' y 'sistema' los genera el servidor, no se aceptan desde la app.
     */
    public function store(Request $request, Conversacion $conversacion)
    {
        $usuario = $request->user();

        if (! $this->esParticipante($conversacion, $usuario->id)) {
            return response()->json(['mensaje' => 'No participas en esta conversacion'], 403);
        }

        // El chat de una cita solo acepta mensajes dentro de su ventana (la regla vive aqui, no solo en la app)
        $conversacion->loadMissing('cita');

        if ($conversacion->cita && $conversacion->tipo !== 'triaje') {
            $estadoChat = $conversacion->cita->estadoChat();

            if ($estadoChat !== 'abierto') {
                return response()->json([
                    'mensaje'     => 'El chat de esta cita no esta disponible',
                    'estado_chat' => $estadoChat,
                ], 423);
            }
        }

        $datos = $request->validate([
            'tipo'        => ['required', Rule::in(['texto', 'imagen', 'archivo'])],
            'contenido'   => ['required', 'string', 'max:5000'],
            'adjunto_url' => [
                Rule::requiredIf(in_array($request->input('tipo'), ['imagen', 'archivo'], true)),
                'nullable', 'string', 'max:255',
            ],
        ]);

        $mensaje = Mensaje::create([
            'conversacion_id'   => $conversacion->id,
            'emisor_id'         => $usuario->id,
            'tipo'              => $datos['tipo'],
            'contenido_cifrado' => Crypt::encryptString($datos['contenido']),
            'adjunto_url'       => $datos['adjunto_url'] ?? null,
            'leido'             => false,
            'enviado_en'        => now(),
        ]);

        $this->avisarMensaje($conversacion, $usuario->id, $datos);

        return response()->json([
            'mensaje'      => 'Mensaje enviado correctamente',
            'mensaje_chat' => $this->formatear($mensaje),
        ], 201);
    }

    /**
     * GET /mensajes/{mensaje}
     */
    public function show(Request $request, Mensaje $mensaje)
    {
        if (! $this->esParticipante($mensaje->conversacion, $request->user()->id)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver este mensaje'], 403);
        }

        return response()->json($this->formatear($mensaje));
    }

    /**
     * PATCH /mensajes/{mensaje}/leer
     * Solo el receptor (un participante que no sea el emisor).
     */
    public function marcarLeido(Request $request, Mensaje $mensaje)
    {
        $usuario = $request->user();

        if (! $this->esParticipante($mensaje->conversacion, $usuario->id)) {
            return response()->json(['mensaje' => 'No tienes permiso sobre este mensaje'], 403);
        }

        if ($mensaje->emisor_id === $usuario->id) {
            return response()->json(['mensaje' => 'No puedes marcar como leido tu propio mensaje'], 422);
        }

        $mensaje->update(['leido' => true]);

        $this->marcarAvisoLeido($usuario->id, $mensaje->conversacion_id);

        return response()->json(['mensaje' => 'Mensaje marcado como leido']);
    }

    /**
     * PATCH /conversaciones/{conversacion}/mensajes/leer
     * Marca como leidos todos los mensajes recibidos (los de otros participantes).
     */
    public function marcarLeidos(Request $request, Conversacion $conversacion)
    {
        $usuario = $request->user();

        if (! $this->esParticipante($conversacion, $usuario->id)) {
            return response()->json(['mensaje' => 'No participas en esta conversacion'], 403);
        }

        $actualizados = $conversacion->mensajes()
            ->where('emisor_id', '!=', $usuario->id)
            ->where('leido', false)
            ->update(['leido' => true]);

        $this->marcarAvisoLeido($usuario->id, $conversacion->id);

        return response()->json([
            'mensaje'      => 'Mensajes marcados como leidos',
            'actualizados' => $actualizados,
        ]);
    }

    /**
     * DELETE /mensajes/{mensaje}  (solo admin, moderacion; lo controla la ruta)
     */
    public function destroy(Mensaje $mensaje)
    {
        $id = $mensaje->id;

        $mensaje->delete();

        BitacoraAuditoria::registrar('eliminar', 'mensaje', $id);

        return response()->json(['mensaje' => 'Mensaje eliminado correctamente']);
    }
}