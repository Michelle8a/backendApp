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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
     * Un adjunto subido por la app vive en el disco privado y se guarda como
     * "chat/{conversacion}/{uuid}__{nombre-original}". Aqui se recupera el
     * nombre original para mostrarlo en la burbuja (ej. "receta.pdf").
     */
    private function nombreAdjunto(Mensaje $m): ?string
    {
        if (! $m->adjunto_url || ! in_array($m->tipo, ['imagen', 'archivo'], true)) {
            return null;
        }

        $base = basename($m->adjunto_url);

        return Str::contains($base, '__') ? Str::after($base, '__') : $base;
    }

    /** true si el valor ya es una URL (http, dama://, etc.) y no una ruta del disco privado. */
    private function esUrlExterna(?string $valor): bool
    {
        return $valor !== null && (bool) preg_match('#^[a-z][a-z0-9+.-]*:#i', $valor);
    }

    /**
     * Devuelve una URL firmada y relativa (sin host) para el adjunto.
     *
     * - Relativa: funciona con Expo Go, el navegador y el proxy de desarrollo
     *   sin depender de APP_URL (que en local cambia en cada PC).
     * - Firmada y temporal: el archivo sigue en disco privado; solo se entrega
     *   a quien tenga el enlace, y el enlace caduca.
     * - La caducidad es fija (inicio del dia dentro de 7 dias) para que el
     *   sondeo cada 5 s no genere una URL distinta en cada respuesta.
     */
    private function urlAdjunto(Mensaje $m): ?string
    {
        $valor = $m->adjunto_url;

        // Los enlaces del bot (ej. dama://agendar) y cualquier URL ya completa se dejan igual
        if (! $valor
            || ! in_array($m->tipo, ['imagen', 'archivo'], true)
            || $this->esUrlExterna($valor)) {
            return $valor;
        }

        return URL::temporarySignedRoute(
            'chat.adjunto',
            now()->addDays(7)->startOfDay(),
            ['mensaje' => $m->id],
            absolute: false,
        );
    }

    /** Borra del disco privado el archivo de un mensaje (si es un adjunto subido por la app). */
    private function borrarAdjunto(Mensaje $m): void
    {
        if ($m->adjunto_url && ! $this->esUrlExterna($m->adjunto_url)) {
            Storage::disk('local')->delete($m->adjunto_url);
        }
    }

    /** Nombre seguro para guardar en disco, conservando algo legible para mostrar. */
    private function nombreParaGuardar(string $nombreOriginal): string
    {
        $base = pathinfo($nombreOriginal, PATHINFO_FILENAME);
        $ext  = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        // Quita acentos, separadores de ruta y caracteres raros sin perder letras/numeros
        $base = Str::ascii($base);
        $base = preg_replace('/[^A-Za-z0-9 ._-]+/', '', $base) ?? '';
        $base = trim(preg_replace('/\s+/', ' ', $base) ?? '');
        $base = Str::limit($base, 70, '');

        return ($base !== '' ? $base : 'archivo') . ($ext !== '' ? ".{$ext}" : '');
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
            'adjunto_url'     => $this->urlAdjunto($m),
            'adjunto_nombre'  => $this->nombreAdjunto($m),
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
     * Acepta JSON (mensaje de texto) o multipart/form-data con un campo "archivo"
     * (jpg, jpeg, png, webp o pdf; maximo 10 MB). El tipo lo decide el servidor.
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

        $archivo = $request->file('archivo');

        $datos = $request->validate([
            // El tipo se deduce del archivo cuando viene un adjunto; para texto se sigue aceptando
            'tipo'      => ['nullable', Rule::in(['texto', 'imagen', 'archivo'])],
            'contenido' => ['nullable', 'string', 'max:5000'],
            'archivo'   => ['nullable', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
        ]);

        $contenido = trim((string) ($datos['contenido'] ?? ''));

        if (! $archivo && $contenido === '') {
            throw ValidationException::withMessages([
                'contenido' => 'Escribe un mensaje o adjunta una imagen o un PDF.',
            ]);
        }

        $tipo = 'texto';
        $rutaAdjunto = null;

        if ($archivo) {
            // La decision la toma el servidor mirando el MIME real, no lo que diga la app
            $tipo = Str::startsWith((string) $archivo->getMimeType(), 'image/') ? 'imagen' : 'archivo';

            $rutaAdjunto = $archivo->storeAs(
                "chat/{$conversacion->id}",
                Str::uuid()->toString() . '__' . $this->nombreParaGuardar($archivo->getClientOriginalName()),
                'local',
            );
        }

        $mensaje = Mensaje::create([
            'conversacion_id'   => $conversacion->id,
            'emisor_id'         => $usuario->id,
            'tipo'              => $tipo,
            'contenido_cifrado' => Crypt::encryptString($contenido),
            'adjunto_url'       => $rutaAdjunto,
            'leido'             => false,
            'enviado_en'        => now(),
        ]);

        $this->avisarMensaje($conversacion, $usuario->id, [
            'tipo'      => $tipo,
            'contenido' => $contenido,
        ]);

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
     * GET /mensajes/{mensaje}/adjunto  (ruta firmada, sin token)
     *
     * Sirve la imagen/PDF desde el disco privado. Solo funciona con el enlace
     * firmado que entrega formatear(); no hay carpeta publica ni URL adivinable.
     */
    public function adjunto(Mensaje $mensaje)
    {
        $ruta = $mensaje->adjunto_url;

        if (! $ruta || ! in_array($mensaje->tipo, ['imagen', 'archivo'], true) || $this->esUrlExterna($ruta)) {
            abort(404);
        }

        $disco = Storage::disk('local');

        if (! $disco->exists($ruta)) {
            abort(404);
        }

        return $disco->response($ruta, $this->nombreAdjunto($mensaje), [
            'Cache-Control'            => 'private, max-age=3600',
            'X-Content-Type-Options'   => 'nosniff',
        ]);
    }

    /**
     * DELETE /mensajes/{mensaje}  (solo admin, moderacion; lo controla la ruta)
     */
    public function destroy(Mensaje $mensaje)
    {
        $id = $mensaje->id;

        $this->borrarAdjunto($mensaje);

        $mensaje->delete();

        BitacoraAuditoria::registrar('eliminar', 'mensaje', $id);

        return response()->json(['mensaje' => 'Mensaje eliminado correctamente']);
    }
}