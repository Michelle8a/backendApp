<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Conversacion;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Mensaje;
use App\Models\Paciente;
use App\Services\DisponibilidadService;
use App\Services\GeminiService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TriajeController extends Controller
{
    private const MAX_MENSAJES_PACIENTE = 12; // tope de costo y de vueltas
    private const MAX_HISTORIAL = 24;         // mensajes que se mandan a Gemini

    // ---------- helpers ----------

    private function guardarBot(Conversacion $c, string $texto, ?string $adjuntoUrl = null): Mensaje
    {
        return Mensaje::create([
            'conversacion_id'   => $c->id,
            'emisor_id'         => null, // el bot no es un usuario
            'tipo'              => 'bot',
            'contenido_cifrado' => Crypt::encryptString($texto),
            'adjunto_url'       => $adjuntoUrl,
            'leido'             => false,
            'enviado_en'        => now(),
        ]);
    }

    private function formatear(Mensaje $m): array
    {
        return [
            'id'          => $m->id,
            'emisor_id'   => $m->emisor_id,
            'tipo'        => $m->tipo,
            'contenido'   => Crypt::decryptString($m->contenido_cifrado),
            'adjunto_url' => $m->adjunto_url,
            'enviado_en'  => $m->enviado_en,
        ];
    }

    private function medicosPara(?int $especialidadId)
    {
        $base = fn () => Medico::where('verificado', true)
            ->orderByDesc('calificacion_promedio')
            ->limit(10);

        $medicos = collect();

        if ($especialidadId) {
            $medicos = $base()
                ->whereHas('especialidades', fn ($q) => $q->where('especialidades.id', $especialidadId))
                ->get(['id', 'nombres', 'apellidos', 'tarifa', 'calificacion_promedio']);
        }

        // sin especialidad, o sin medicos en ella -> medicina general
        if ($medicos->isEmpty()) {
            $medicos = $base()->where('es_general', true)
                ->get(['id', 'nombres', 'apellidos', 'tarifa', 'calificacion_promedio']);
        }

        return $medicos;
    }

    private function conversacionDelPaciente(Request $request, Conversacion $c): ?JsonResponse
    {
        $esParticipante = $c->participantes()->where('usuarios.id', $request->user()->id)->exists();

        if ($c->tipo !== 'triaje' || ! $esParticipante) {
            return response()->json(['mensaje' => 'Conversacion no valida'], 403);
        }
        return null;
    }

    /** Une la conversacion de admision con la cita: el medico entra al chat y recibe el resumen. */
    private function enlazarCita(Conversacion $conversacion, Citas $cita): void
    {
        $conversacion->update(['cita_id' => $cita->id, 'tipo' => 'consulta']);
        $conversacion->participantes()->syncWithoutDetaching([$cita->medico->usuario_id]);

        if ($conversacion->resumen_triaje) {
            $this->guardarBot(
                $conversacion,
                "Resumen de admision para el medico:\n" . Crypt::decryptString($conversacion->resumen_triaje)
            );
        }
    }

    /** Frases que siempre disparan el aviso de emergencia (lista conservadora: ajustala con tu equipo medico). */
    private function esEmergenciaObvia(string $texto): bool
    {
        $t = Str::of($texto)->ascii()->lower()->value();

        $frases = [
            'no puedo respirar', 'me ahogo', 'dolor de pecho', 'dolor en el pecho',
            'perdi el conocimiento', 'me desmaye', 'convulsion', 'no deja de sangrar',
            'sangrado abundante', 'me quiero morir', 'quiero morir', 'quitarme la vida',
            'suicid', 'hacerme dano', 'no siento un lado', 'cara torcida',
        ];

        foreach ($frases as $frase) {
            if (str_contains($t, $frase)) {
                return true;
            }
        }
        return false;
    }

    // ---------- endpoints ----------

    /** POST /triaje  -> crea la conversacion de admision con el bot */
    public function iniciar(Request $request): JsonResponse
    {
        $usuario  = $request->user();
        $paciente = Paciente::where('usuario_id', $usuario->id)->first();

        if (! $paciente) {
            return response()->json(['mensaje' => 'Primero completa tu perfil de paciente'], 404);
        }

        // Si ya hay una admision en curso, se reutiliza (evita saludos y conversaciones duplicadas)
        $existente = Conversacion::where('paciente_id', $paciente->id)
            ->where('tipo', 'triaje')
            ->where('estado_triaje', 'en_curso')
            ->latest('id')
            ->first();

        if ($existente) {
            return response()->json(['conversacion_id' => $existente->id], 200);
        }

        $conversacion = DB::transaction(function () use ($paciente, $usuario) {
            $c = Conversacion::create([
                'paciente_id'  => $paciente->id,
                'tipo'         => 'triaje',
                'estado_triaje'=> 'en_curso',
                'creado_en'    => now(),
            ]);
            $c->participantes()->attach($usuario->id);

            $this->guardarBot($c,
                'Hola, soy el asistente de admision. Te hare unas preguntas breves para ' .
                'orientarte con el especialista adecuado. No sustituyo a un medico. ' .
                'Cuentame, ¿cual es el motivo de tu consulta?'
            );

            return $c;
        });

        return response()->json(['conversacion_id' => $conversacion->id], 201);
    }

    /** POST /triaje/{conversacion}/mensajes {contenido} */
    public function enviar(Request $request, Conversacion $conversacion, GeminiService $gemini): JsonResponse
    {
        if ($error = $this->conversacionDelPaciente($request, $conversacion)) {
            return $error;
        }

        if ($conversacion->estado_triaje !== 'en_curso') {
            return response()->json(['mensaje' => 'La admision ya termino'], 409);
        }

        $datos = $request->validate(['contenido' => 'required|string|max:1000']);

        // 1. guardar mensaje del paciente (queda guardado aunque Gemini falle)
        $mensajePaciente = Mensaje::create([
            'conversacion_id'   => $conversacion->id,
            'emisor_id'         => $request->user()->id,
            'tipo'              => 'texto',
            'contenido_cifrado' => Crypt::encryptString($datos['contenido']),
            'leido'             => true,
            'enviado_en'        => now(),
        ]);

        // 1b. Red de seguridad: una emergencia obvia no debe depender de que la IA responda
        if ($this->esEmergenciaObvia($datos['contenido'])) {
            $mensajeBot = $this->guardarBot(
                $conversacion,
                'Lo que describes puede ser una emergencia. Llama al 911 o a tu contacto de emergencia ahora mismo; no esperes una consulta en la app.'
            );
            $conversacion->update(['estado_triaje' => 'urgente']);

            return response()->json([
                'mensaje_paciente' => $this->formatear($mensajePaciente),
                'mensaje_bot'      => $this->formatear($mensajeBot),
                'listo'            => true,
                'urgencia_alta'    => true,
                'medicos'          => [],
                'especialidad_id'  => null,
            ]);
        }

        // 2. historial para Gemini (bot = 'model', paciente = 'user')
        $historial = $conversacion->mensajes()->orderByDesc('id')->limit(self::MAX_HISTORIAL)->get()
            ->reverse()->values()
            ->map(fn ($m) => [
                'role' => $m->emisor_id === null ? 'model' : 'user',
                'text' => Crypt::decryptString($m->contenido_cifrado),
            ])->all();

        // Gemini exige que el primer turno sea 'user'
        while ($historial && $historial[0]['role'] === 'model') {
            array_shift($historial);
        }

        $turnosPaciente = $conversacion->mensajes()->whereNotNull('emisor_id')->count();
        $forzar = $turnosPaciente >= self::MAX_MENSAJES_PACIENTE;

        $especialidades = Especialidad::select('id', 'nombre')->get()->toArray(); // ajusta 'nombre' si tu columna se llama distinto

        // 3. llamar a Gemini
        try {
            $ia = $gemini->triaje($historial, $especialidades, $forzar);
        } catch (\Throwable $e) {
            Log::error('Triaje Gemini: ' . $e->getMessage());
            return response()->json([
                'mensaje' => 'El asistente no esta disponible ahora, intenta de nuevo en un momento',
            ], 503);
        }

        // 4. validar lo que devolvio la IA (nunca confiar a ciegas)
        $especialidadId = $ia['especialidad_id'];
        if ($especialidadId && ! Especialidad::whereKey($especialidadId)->exists()) {
            $especialidadId = null;
        }

        $mensajeBot = $this->guardarBot($conversacion, $ia['respuesta']);

        $respuesta = [
            'mensaje_paciente' => $this->formatear($mensajePaciente),
            'mensaje_bot'      => $this->formatear($mensajeBot),
            'listo'            => $ia['listo'],
            'urgencia_alta'    => $ia['urgencia_alta'],
            'medicos'          => [],
            'especialidad_id'  => null,
        ];

        // 5. cierre de la admision
        if ($ia['listo']) {
            $conversacion->update([
                'estado_triaje'            => $ia['urgencia_alta'] ? 'urgente' : 'completado',
                'especialidad_sugerida_id' => $especialidadId,
                'resumen_triaje'           => $ia['resumen'] ? Crypt::encryptString($ia['resumen']) : null,
            ]);

            if (! $ia['urgencia_alta']) {
                $respuesta['especialidad_id'] = $especialidadId;
                $respuesta['medicos']         = $this->medicosPara($especialidadId);
            }
        }

        return response()->json($respuesta);
    }

    /**
     * POST /triaje/{conversacion}/vincular-cita {cita_id}
     * Se llama despues de POST /citas. Reutiliza ESTA conversacion para la cita:
     * el medico entra como participante y ve el resumen de la admision.
     */
    public function vincularCita(Request $request, Conversacion $conversacion): JsonResponse
    {
        if ($error = $this->conversacionDelPaciente($request, $conversacion)) {
            return $error;
        }

        $datos = $request->validate(['cita_id' => 'required|integer|exists:citas,id']);

        $cita = Citas::with(['paciente', 'medico'])->findOrFail($datos['cita_id']);

        if ($cita->paciente_id !== $conversacion->paciente_id) {
            return response()->json(['mensaje' => 'La cita no es tuya'], 403);
        }
        if ($conversacion->cita_id || Conversacion::where('cita_id', $cita->id)->exists()) {
            return response()->json(['mensaje' => 'Ya vinculada'], 409);
        }

        DB::transaction(fn () => $this->enlazarCita($conversacion, $cita));

        return response()->json(['mensaje' => 'Conversacion vinculada a la cita', 'conversacion_id' => $conversacion->id]);
    }

    /**
     * POST /triaje/{conversacion}/agendar
     * Body: { "medico_id": 4, "fecha_hora": "2026-11-13 09:00" }
     *
     * Crea la cita (pendiente) y la une a esta conversacion. La hora se revalida en el servidor
     * contra los horarios del medico y las citas existentes; la IA nunca decide ni escribe horarios.
     */
    public function agendar(Request $request, Conversacion $conversacion, DisponibilidadService $disponibilidad): JsonResponse
    {
        if ($error = $this->conversacionDelPaciente($request, $conversacion)) {
            return $error;
        }

        if ($conversacion->estado_triaje !== 'completado' || $conversacion->cita_id) {
            return response()->json(['mensaje' => 'Esta admision no puede agendar una cita'], 409);
        }

        $datos = $request->validate([
            'medico_id'  => 'required|integer|exists:medicos,id',
            'fecha_hora' => 'required|date_format:Y-m-d H:i',
        ]);

        if (! Medico::findOrFail($datos['medico_id'])->verificado) {
            return response()->json(['mensaje' => 'El medico aun no esta verificado'], 422);
        }

        $inicio   = Carbon::createFromFormat('Y-m-d H:i', $datos['fecha_hora'], config('app.timezone'));
        $duracion = DisponibilidadService::DURACION_DEFAULT;

        $cita = DB::transaction(function () use ($datos, $conversacion, $inicio, $duracion, $disponibilidad) {
            // Bloquea la fila del medico: dos pacientes no pueden tomar la misma hora a la vez
            $medico = Medico::whereKey($datos['medico_id'])->lockForUpdate()->first();

            if (! $disponibilidad->estaLibre($medico, $inicio, $duracion, $conversacion->paciente_id)) {
                return null;
            }

            $especialidad = $conversacion->especialidad_sugerida_id
                ? Especialidad::find($conversacion->especialidad_sugerida_id)
                : null;

            // El motivo de la cita va en texto plano: se deja generico y el detalle clinico
            // viaja cifrado en el chat (resumen de admision).
            $motivo = $especialidad
                ? "Consulta de {$especialidad->nombre} (admision asistida)"
                : 'Consulta de medicina general (admision asistida)';

            $cita = Citas::create([
                'paciente_id'           => $conversacion->paciente_id,
                'medico_id'             => $medico->id,
                'fecha_hora'            => $inicio,
                'duracion_min'          => $duracion,
                'tipo'                  => $especialidad ? 'especialista' : 'general',
                'estado'                => 'pendiente',
                'motivo'                => $motivo,
                'sala_video'            => 'cita-' . Str::uuid(),
                'notas_medico_cifradas' => null,
            ]);

            $this->enlazarCita($conversacion, $cita);

            return $cita;
        });

        if (! $cita) {
            return response()->json(['mensaje' => 'Ese horario ya no esta disponible, elige otro'], 409);
        }

        return response()->json([
            'mensaje'         => 'Cita agendada. El medico debe confirmarla.',
            'cita'            => [
                'id'           => $cita->id,
                'medico_id'    => $cita->medico_id,
                'fecha_hora'   => $inicio->format('Y-m-d H:i'),
                'duracion_min' => $duracion,
                'estado'       => 'pendiente',
            ],
            'conversacion_id' => $conversacion->id,
        ], 201);
    }
}