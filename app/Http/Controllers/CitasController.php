<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Conversacion;
use App\Models\Medico;
use App\Models\Mensaje;
use App\Models\Paciente;
use App\Services\DisponibilidadService;
use App\Services\Notificador;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CitasController extends Controller
{
    // Transiciones de estado permitidas
    private const TRANSICIONES = [
        'pendiente'  => ['confirmada', 'cancelada'],
        'confirmada' => ['en_curso', 'cancelada', 'no_asistio'],
        'en_curso'   => ['finalizada', 'no_asistio'],
        'finalizada' => [],
        'cancelada'  => [],
        'no_asistio' => [],
    ];

    // Minutos despues de la hora de la cita antes de poder marcar "no asistio"
    private const GRACIA_NO_ASISTIO_MIN = 15;

    private const ESTADOS = ['pendiente', 'confirmada', 'en_curso', 'cancelada', 'finalizada', 'no_asistio'];

    // ------------------------------------------------------------------
    // HELPERS
    // ------------------------------------------------------------------

    private function pacienteDe($usuario): ?Paciente
    {
        return Paciente::where('usuario_id', $usuario->id)->first();
    }

    private function medicoDe($usuario): ?Medico
    {
        return Medico::where('usuario_id', $usuario->id)->first();
    }

    /**
     * Devuelve null si el usuario puede acceder a la cita, o un 403 si no.
     * admin: todas | paciente: las suyas | medico: las suyas
     */
    private function verificarAcceso($usuario, Citas $cita): ?JsonResponse
    {
        if ($usuario->rol === 'admin') {
            return null;
        }

        if ($usuario->rol === 'paciente') {
            $paciente = $this->pacienteDe($usuario);
            if ($paciente && (int) $paciente->id === (int) $cita->paciente_id) {
                return null;
            }
        }

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            if ($medico && (int) $medico->id === (int) $cita->medico_id) {
                return null;
            }
        }

        return response()->json(['mensaje' => 'No tienes acceso a esta cita'], 403);
    }

    /**
     * Revisa choque de horario para el medico y para el paciente.
     * Ignora citas canceladas, finalizadas y no asistidas.
     */
    private function hayConflicto(int $medicoId, int $pacienteId, Carbon $inicio, int $duracion, ?int $ignorarId = null): ?string
    {
        $fin = $inicio->copy()->addMinutes($duracion);

        $base = fn ($campo, $valor) => Citas::where($campo, $valor)
            ->whereNotIn('estado', ['cancelada', 'finalizada', 'no_asistio'])
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->where('fecha_hora', '<', $fin)
            ->whereRaw('DATE_ADD(fecha_hora, INTERVAL duracion_min MINUTE) > ?', [$inicio]);

        if ($base('medico_id', $medicoId)->exists()) {
            return 'El medico ya tiene una cita en ese horario';
        }

        if ($base('paciente_id', $pacienteId)->exists()) {
            return 'El paciente ya tiene una cita en ese horario';
        }

        return null;
    }

    /**
     * Prepara la cita para la respuesta.
     * Las notas del medico solo las ven medico y admin (nunca el paciente).
     * Agrega estado_chat (esperando_confirmacion|programado|abierto|cerrado)
     * para que la app pinte colores y avisos sin recalcular.
     */
    private function transformar(Citas $cita, $usuario, bool $conNotas = true): array
    {
        $data = $cita->toArray();
        unset($data['notas_medico_cifradas']);

        $data['estado_chat'] = $cita->estadoChat();

        if ($conNotas && in_array($usuario->rol, ['medico', 'admin'], true) && $cita->notas_medico_cifradas) {
            try {
                $data['notas_medico'] = Crypt::decryptString($cita->notas_medico_cifradas);
            } catch (DecryptException $e) {
                $data['notas_medico'] = null;
            }
        }

        // El paciente puede reprogramar si cancelo el otro lado o si no se conectaron
        if ($conNotas && $usuario->rol === 'paciente') {
            $elOtroCancelo = $cita->estado === 'cancelada' && (int) $cita->cancelada_por !== (int) $usuario->id;
            $data['puede_reprogramar'] = ($elOtroCancelo || $cita->estado === 'no_asistio')
                && ! Citas::where('reprogramada_de', $cita->id)->exists();
        }

        return $data;
    }

    // ------------------------------------------------------------------
    // NOTIFICACIONES PUSH Y MENSAJES DE SISTEMA
    // ------------------------------------------------------------------

    private function cuando(Citas $cita): string
    {
        return Carbon::parse($cita->fecha_hora)->locale('es')->isoFormat('ddd D MMM, h:mm A');
    }

    /**
     * $a = 'medico' o 'paciente': a quien se le avisa.
     * Texto generico: se ve en la pantalla bloqueada.
     * Nunca rompe la peticion: si falla, queda en el log.
     */
    private function avisar(Citas $cita, string $a, string $titulo, string $cuerpo, array $extra = []): void
    {
        try {
            $usuarioId = $a === 'medico'
                ? Medico::whereKey($cita->medico_id)->value('usuario_id')
                : Paciente::whereKey($cita->paciente_id)->value('usuario_id');

            if (! $usuarioId) {
                return;
            }

            Notificador::notificar(
                (int) $usuarioId,
                'cita',
                $titulo,
                $cuerpo,
                array_merge(['pantalla' => 'cita', 'cita_id' => $cita->id], $extra),
                $cita->id
            );
        } catch (\Throwable $e) {
            Log::error('Fallo el aviso de cita: ' . $e->getMessage());
        }
    }

    /** Avisa a la otra parte: el que cancela no se notifica a si mismo. */
    private function avisarCancelacion(Citas $cita, $usuario): void
    {
        $cuando = $this->cuando($cita);

        if ($usuario->rol === 'paciente') {
            $this->avisar($cita, 'medico', 'Cita cancelada', "El paciente canceló la cita del {$cuando}.");
            return;
        }

        $this->avisar(
            $cita,
            'paciente',
            'Tu cita fue cancelada',
            "El médico canceló tu cita del {$cuando}. Puedes reprogramarla desde la app.",
            ['accion' => 'reprogramar']
        );

        if ($usuario->rol === 'admin') {
            $this->avisar($cita, 'medico', 'Cita cancelada', "La cita del {$cuando} fue cancelada.");
        }
    }

    /** Mensaje centrado dentro del chat ("La cita fue cancelada...") */
    private function mensajeSistema(Citas $cita, string $texto): void
    {
        try {
            $conversacion = Conversacion::where('cita_id', $cita->id)->first();
            if (! $conversacion) {
                return;
            }

            Mensaje::create([
                'conversacion_id'   => $conversacion->id,
                'emisor_id'         => null,
                'tipo'              => 'sistema',
                'contenido_cifrado' => Crypt::encryptString($texto),
                'adjunto_url'       => null,
                'leido'             => false,
                'enviado_en'        => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear el mensaje de sistema: ' . $e->getMessage());
        }
    }

    /** Crea el chat de la cita con paciente y médico. Si ya existe, no hace nada. */
    private function crearConversacion(Citas $cita): void
    {
        try {
            if (Conversacion::where('cita_id', $cita->id)->exists()) {
                return;
            }

            $usuarios = array_filter([
                Paciente::whereKey($cita->paciente_id)->value('usuario_id'),
                Medico::whereKey($cita->medico_id)->value('usuario_id'),
            ]);

            DB::transaction(function () use ($cita, $usuarios) {
                $c = Conversacion::create([
                    'cita_id'   => $cita->id,
                    'creado_en' => now(),
                ]);
                $c->participantes()->attach($usuarios);
            });
        } catch (\Throwable $e) {
            Log::warning('No se pudo crear la conversacion de la cita: ' . $e->getMessage());
        }
    }

    /** Una sola puerta para cancelar: la usan /cancelar y /estado */
    private function aplicarCancelacion(Citas $cita, $usuario, ?string $motivo): void
    {
        $porMedico = $usuario->rol !== 'paciente';

        $cambios = [
            'estado'             => 'cancelada',
            'cancelada_por'      => $usuario->id,
            'motivo_cancelacion' => $motivo ?: ($porMedico ? 'Cancelada por el médico' : 'Cancelada por el paciente'),
            'cancelada_en'       => now(),
        ];

        // Si cancela el medico y ya estaba pagada, queda pendiente de reembolso.
        // Si cancela el paciente, la regla se define en el paso del pago.
        if ($porMedico && $cita->pago_estado === 'pagado') {
            $cambios['pago_estado'] = 'reembolso_pendiente';
        }

        $cita->update($cambios);

        $this->mensajeSistema($cita, $porMedico
            ? 'El médico canceló esta cita.'
            : 'El paciente canceló esta cita.');

        $this->avisarCancelacion($cita, $usuario);
    }

    // ------------------------------------------------------------------
    // GET /citas
    // admin: todas | medico: las suyas | paciente: las suyas
    // Filtros: ?estado=pendiente&desde=2026-11-01&hasta=2026-11-30&orden=asc&per_page=15
    // ------------------------------------------------------------------
    public function index(Request $request)
    {
        $usuario = $request->user();

        $request->validate([
            'estado'   => ['nullable', Rule::in(self::ESTADOS)],
            'desde'    => 'nullable|date',
            'hasta'    => 'nullable|date|after_or_equal:desde',
            'orden'    => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $query = Citas::with([
            'paciente:id,nombres,apellidos',
            'medico:id,nombres,apellidos,tarifa',
        ]);

        if ($usuario->rol === 'paciente') {
            $paciente = $this->pacienteDe($usuario);
            if (! $paciente) {
                return response()->json(['mensaje' => 'Tu usuario no tiene perfil de paciente'], 404);
            }
            $query->where('paciente_id', $paciente->id);
        } elseif ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            if (! $medico) {
                return response()->json(['mensaje' => 'Tu usuario no tiene perfil de medico'], 404);
            }
            $query->where('medico_id', $medico->id);
        }

        $query->when($request->estado, fn ($q, $v) => $q->where('estado', $v))
              ->when($request->desde, fn ($q, $v) => $q->whereDate('fecha_hora', '>=', $v))
              ->when($request->hasta, fn ($q, $v) => $q->whereDate('fecha_hora', '<=', $v))
              ->orderBy('fecha_hora', $request->input('orden', 'desc'));

        $citas = $query->paginate($request->integer('per_page', 15));
        $citas->through(fn ($c) => $this->transformar($c, $usuario, false));

        return response()->json($citas);
    }

    // ------------------------------------------------------------------
    // POST /citas
    // paciente (para si mismo) y admin (debe mandar paciente_id)
    // ------------------------------------------------------------------
    public function store(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'paciente_id'  => [$usuario->rol === 'admin' ? 'required' : 'prohibited', 'integer', 'exists:pacientes,id'],
            'medico_id'    => 'required|integer|exists:medicos,id',
            'fecha_hora'   => 'required|date|after:now',
            'duracion_min' => 'required|integer|min:15|max:120',
            'tipo'         => ['required', Rule::in(['general', 'especialista'])],
            'motivo'       => 'required|string|max:255',
        ]);

        if ($usuario->rol === 'paciente') {
            $paciente = $this->pacienteDe($usuario);
            if (! $paciente) {
                return response()->json(['mensaje' => 'Primero debes crear tu perfil de paciente'], 404);
            }
            $datos['paciente_id'] = $paciente->id;
        }

        $medico = Medico::find($datos['medico_id']);
        if (! $medico->verificado) {
            return response()->json(['mensaje' => 'El medico aun no esta verificado'], 422);
        }

        $inicio = Carbon::parse($datos['fecha_hora']);

        if ($mensaje = $this->hayConflicto($datos['medico_id'], $datos['paciente_id'], $inicio, $datos['duracion_min'])) {
            return response()->json(['mensaje' => $mensaje], 409);
        }

        $cita = Citas::create([
            'paciente_id'           => $datos['paciente_id'],
            'medico_id'             => $datos['medico_id'],
            'fecha_hora'            => $inicio,
            'duracion_min'          => $datos['duracion_min'],
            'tipo'                  => $datos['tipo'],
            'estado'                => 'pendiente',
            'motivo'                => $datos['motivo'],
            'sala_video'            => 'cita-' . Str::uuid(), // se genera automaticamente
            'notas_medico_cifradas' => null,
        ]);

        $this->crearConversacion($cita);

        // aviso push al medico
        $this->avisar($cita, 'medico', 'Nueva solicitud de cita', 'Tienes una cita por confirmar: ' . $this->cuando($cita));

        $cita->load(['paciente:id,nombres,apellidos', 'medico:id,nombres,apellidos,tarifa']);

        return response()->json([
            'mensaje' => 'Cita creada correctamente',
            'data'    => $this->transformar($cita, $usuario),
        ], 201);
    }

    // ------------------------------------------------------------------
    // GET /citas/{cita}
    // ------------------------------------------------------------------
    public function show(Request $request, Citas $cita)
    {
        $usuario = $request->user();

        if ($denegado = $this->verificarAcceso($usuario, $cita)) {
            return $denegado;
        }

        $cita->load([
            'paciente:id,nombres,apellidos',
            'medico:id,nombres,apellidos,tarifa',
            'conversacion',
            'receta',
        ]);

        return response()->json(['data' => $this->transformar($cita, $usuario)]);
    }

    // ------------------------------------------------------------------
    // PUT /citas/{cita}
    // paciente: reprograma/edita su cita (solo pendiente o confirmada)
    // medico:   agrega 'notas_medico' (esto FINALIZA la consulta)
    // admin:    todo
    // ------------------------------------------------------------------
    public function update(Request $request, Citas $cita)
    {
        $usuario = $request->user();

        if ($denegado = $this->verificarAcceso($usuario, $cita)) {
            return $denegado;
        }

        // -------- MEDICO: notas = cierre de la consulta --------
        if ($usuario->rol === 'medico') {
            $datos = $request->validate(['notas_medico' => 'required|string|max:5000']);

            if (in_array($cita->estado, ['pendiente', 'cancelada', 'no_asistio'], true)) {
                return response()->json(['mensaje' => "No se pueden adjuntar notas en una cita '{$cita->estado}'"], 422);
            }
            if ($cita->estado !== 'finalizada' && now()->lt($cita->aperturaChat())) {
                return response()->json(['mensaje' => 'La consulta aun no inicia'], 422);
            }

            $seFinaliza = in_array($cita->estado, ['confirmada', 'en_curso'], true);

            DB::transaction(function () use ($cita, $datos, $seFinaliza) {
                $cambios = ['notas_medico_cifradas' => Crypt::encryptString($datos['notas_medico'])];
                if ($seFinaliza) {
                    $cambios['estado']        = 'finalizada';
                    $cambios['finalizada_en'] = now();
                }
                $cita->update($cambios);
            });

            if ($seFinaliza) {
                $this->mensajeSistema($cita, 'La consulta finalizó. Este chat ahora es de solo lectura.');
                $this->avisar($cita, 'paciente', 'Consulta finalizada', 'Tu consulta del ' . $this->cuando($cita) . ' ha finalizado.');
            }

            return response()->json([
                'mensaje' => $seFinaliza ? 'Notas guardadas, consulta finalizada' : 'Notas actualizadas',
                'data'    => $this->transformar($cita->fresh(), $usuario),
            ]);
        }

        // -------- PACIENTE / ADMIN --------
        if (! in_array($cita->estado, ['pendiente', 'confirmada'], true)) {
            return response()->json(['mensaje' => "No se puede editar una cita en estado '{$cita->estado}'"], 422);
        }

        $reglas = [
            'fecha_hora'   => 'sometimes|date|after:now',
            'duracion_min' => 'sometimes|integer|min:15|max:120',
            'tipo'         => ['sometimes', Rule::in(['general', 'especialista'])],
            'motivo'       => 'sometimes|string|max:255',
        ];

        if ($usuario->rol === 'admin') {
            $reglas['notas_medico'] = 'sometimes|nullable|string|max:5000';
        }

        $datos = $request->validate($reglas);

        $inicio   = Carbon::parse($datos['fecha_hora'] ?? $cita->fecha_hora);
        $duracion = $datos['duracion_min'] ?? $cita->duracion_min;

        if (isset($datos['fecha_hora']) || isset($datos['duracion_min'])) {
            if ($mensaje = $this->hayConflicto($cita->medico_id, $cita->paciente_id, $inicio, $duracion, $cita->id)) {
                return response()->json(['mensaje' => $mensaje], 409);
            }
        }

        $reprogramada = isset($datos['fecha_hora']);

        if ($reprogramada) {
            $datos['fecha_hora'] = $inicio;
            $datos['estado'] = 'pendiente'; // al reprogramar, el medico debe volver a confirmar
        }

        if (array_key_exists('notas_medico', $datos)) {
            $datos['notas_medico_cifradas'] = $datos['notas_medico'] ? Crypt::encryptString($datos['notas_medico']) : null;
            unset($datos['notas_medico']);
        }

        $cita->update($datos);

        $actualizada = $cita->fresh();

        // aviso push al medico si cambio la fecha
        if ($reprogramada) {
            $this->avisar($actualizada, 'medico', 'Cita reprogramada', 'Una cita cambió a ' . $this->cuando($actualizada));
            $this->mensajeSistema($actualizada, 'La cita se reprogramó para el ' . $this->cuando($actualizada) . '.');
        }

        return response()->json([
            'mensaje' => 'Cita actualizada correctamente',
            'data'    => $this->transformar($actualizada, $usuario),
        ]);
    }

    // ------------------------------------------------------------------
    // PATCH /citas/{cita}/estado   (medico de la cita y admin)
    // Body: { "estado": "cancelada", "motivo": "opcional" }
    // ------------------------------------------------------------------
    public function cambiarEstado(Request $request, Citas $cita)
    {
        $usuario = $request->user();

        if ($denegado = $this->verificarAcceso($usuario, $cita)) {
            return $denegado;
        }

        $datos = $request->validate([
            'estado' => ['required', Rule::in(['confirmada', 'en_curso', 'cancelada', 'finalizada', 'no_asistio'])],
            'motivo' => 'nullable|string|max:255',
        ]);

        if (! in_array($datos['estado'], self::TRANSICIONES[$cita->estado], true)) {
            return response()->json([
                'mensaje' => "No se puede pasar de '{$cita->estado}' a '{$datos['estado']}'",
            ], 422);
        }

        if ($datos['estado'] === 'cancelada') {
            $this->aplicarCancelacion($cita, $usuario, $datos['motivo'] ?? null);

        } elseif ($datos['estado'] === 'no_asistio') {
            if (now()->lt($cita->fecha_hora->copy()->addMinutes(self::GRACIA_NO_ASISTIO_MIN))) {
                return response()->json(['mensaje' => 'Aun no pasa el tiempo de espera para marcar inasistencia'], 422);
            }

            $cita->update(['estado' => 'no_asistio']);

            $this->mensajeSistema($cita, 'La cita se marcó como no asistida.');
            $this->avisar(
                $cita,
                'paciente',
                'No se realizó tu cita',
                'Tu cita del ' . $this->cuando($cita) . ' no se realizó. Puedes reprogramarla desde la app.',
                ['accion' => 'reprogramar']
            );

        } elseif ($datos['estado'] === 'finalizada') {
            $cita->update(['estado' => 'finalizada', 'finalizada_en' => now()]);

            $this->mensajeSistema($cita, 'La consulta finalizó. Este chat ahora es de solo lectura.');
            $this->avisar($cita, 'paciente', 'Consulta finalizada', 'Tu consulta del ' . $this->cuando($cita) . ' ha finalizado.');

        } else {
            $cita->update(['estado' => $datos['estado']]);

            if ($datos['estado'] === 'confirmada') {
                $this->avisar($cita, 'paciente', 'Cita confirmada', 'Tu cita fue confirmada para el ' . $this->cuando($cita));
            }
        }

        return response()->json([
            'mensaje' => "Cita {$datos['estado']}",
            'data'    => $this->transformar($cita->fresh(), $usuario),
        ]);
    }

    // ------------------------------------------------------------------
    // PATCH /citas/{cita}/cancelar   (paciente, medico y admin; cada uno sobre las suyas)
    // Body opcional: { "motivo": "..." }
    // ------------------------------------------------------------------
    public function cancelar(Request $request, Citas $cita)
    {
        $usuario = $request->user();

        if ($denegado = $this->verificarAcceso($usuario, $cita)) {
            return $denegado;
        }

        if (! in_array($cita->estado, ['pendiente', 'confirmada'], true)) {
            return response()->json(['mensaje' => "No se puede cancelar una cita en estado '{$cita->estado}'"], 422);
        }

        $datos = $request->validate(['motivo' => 'nullable|string|max:255']);

        $this->aplicarCancelacion($cita, $usuario, $datos['motivo'] ?? null);

        return response()->json([
            'mensaje' => 'Cita cancelada correctamente',
            'data'    => $this->transformar($cita->fresh(), $usuario),
        ]);
    }

    // ------------------------------------------------------------------
    // POST /citas/{cita}/reprogramar   (solo paciente)
    // Body: { "fecha_hora": "2026-11-20 09:00", "medico_id": 4 (opcional) }
    // Crea una cita NUEVA ligada a la anterior (cancelada por el otro lado o no asistida).
    // ------------------------------------------------------------------
    public function reprogramar(Request $request, Citas $cita, DisponibilidadService $disponibilidad)
    {
        $usuario  = $request->user();
        $paciente = $this->pacienteDe($usuario);

        if (! $paciente || (int) $paciente->id !== (int) $cita->paciente_id) {
            return response()->json(['mensaje' => 'No tienes acceso a esta cita'], 403);
        }

        $canceloOtro = $cita->estado === 'cancelada' && (int) $cita->cancelada_por !== (int) $usuario->id;
        if (! ($canceloOtro || $cita->estado === 'no_asistio')) {
            return response()->json(['mensaje' => 'Esta cita no se puede reprogramar'], 422);
        }

        if (Citas::where('reprogramada_de', $cita->id)->exists()) {
            return response()->json(['mensaje' => 'Esta cita ya fue reprogramada'], 409);
        }

        $datos = $request->validate([
            'fecha_hora' => 'required|date_format:Y-m-d H:i',
            'medico_id'  => 'sometimes|integer|exists:medicos,id',
        ]);

        $inicio = Carbon::createFromFormat('Y-m-d H:i', $datos['fecha_hora'], config('app.timezone'));
        if ($inicio->isPast()) {
            return response()->json(['mensaje' => 'Elige una fecha futura'], 422);
        }

        $medicoId = $datos['medico_id'] ?? $cita->medico_id;

        $nueva = DB::transaction(function () use ($cita, $medicoId, $inicio, $disponibilidad) {
            // Bloquea la fila del medico: dos pacientes no pueden tomar la misma hora a la vez
            $medico = Medico::whereKey($medicoId)->lockForUpdate()->first();

            if (! $medico->verificado) {
                return 'no_verificado';
            }
            if (! $disponibilidad->estaLibre($medico, $inicio, $cita->duracion_min, $cita->paciente_id)) {
                return null;
            }

            return Citas::create([
                'paciente_id'           => $cita->paciente_id,
                'medico_id'             => $medico->id,
                'fecha_hora'            => $inicio,
                'duracion_min'          => $cita->duracion_min,
                'tipo'                  => $cita->tipo,
                'estado'                => 'pendiente',
                'motivo'                => $cita->motivo,
                'sala_video'            => 'cita-' . Str::uuid(),
                'notas_medico_cifradas' => null,
                'reprogramada_de'       => $cita->id,
            ]);
        });

        if ($nueva === 'no_verificado') {
            return response()->json(['mensaje' => 'El medico aun no esta verificado'], 422);
        }
        if (! $nueva) {
            return response()->json(['mensaje' => 'Ese horario ya no esta disponible, elige otro'], 409);
        }

        $this->crearConversacion($nueva);
        $this->avisar($nueva, 'medico', 'Nueva solicitud de cita', 'Tienes una cita por confirmar: ' . $this->cuando($nueva));

        return response()->json([
            'mensaje' => 'Cita reprogramada, el medico debe confirmarla',
            'data'    => $this->transformar($nueva->load(['paciente:id,nombres,apellidos', 'medico:id,nombres,apellidos,tarifa']), $usuario),
        ], 201);
    }

    // ------------------------------------------------------------------
    // DELETE /citas/{cita}   (solo admin)
    // ------------------------------------------------------------------
    public function destroy(Citas $cita)
    {
        $cita->delete();

        return response()->json(['mensaje' => 'Cita eliminada correctamente']);
    }
}