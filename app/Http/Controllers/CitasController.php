<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Medico;
use App\Models\Paciente;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CitasController extends Controller
{
    //Transiciones de estado permitidas
    private const TRANSICIONES = [
        'pendiente'  => ['confirmada', 'cancelada'],
        'confirmada' => ['en_curso', 'cancelada'],
        'en_curso'   => ['finalizada'],
        'finalizada' => [],
        'cancelada'  => [],
    ];

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
            if ($paciente && $paciente->id === $cita->paciente_id) {
                return null;
            }
        }

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            if ($medico && $medico->id === $cita->medico_id) {
                return null;
            }
        }

        return response()->json(['mensaje' => 'No tienes acceso a esta cita'], 403);
    }

    /**
     * Revisa choque de horario para el medico y para el paciente.
     * Ignora citas canceladas/finalizadas.
     */
    private function hayConflicto(int $medicoId, int $pacienteId, Carbon $inicio, int $duracion, ?int $ignorarId = null): ?string
    {
        $fin = $inicio->copy()->addMinutes($duracion);

        $base = fn ($campo, $valor) => Citas::where($campo, $valor)
            ->whereNotIn('estado', ['cancelada', 'finalizada'])
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
     */
    private function transformar(Citas $cita, $usuario, bool $conNotas = true): array
    {
        $data = $cita->toArray();
        unset($data['notas_medico_cifradas']);

        if ($conNotas && in_array($usuario->rol, ['medico', 'admin'], true) && $cita->notas_medico_cifradas) {
            try {
                $data['notas_medico'] = Crypt::decryptString($cita->notas_medico_cifradas);
            } catch (DecryptException $e) {
                $data['notas_medico'] = null;
            }
        }

        return $data;
    }

    // ------------------------------------------------------------------
    // GET /citas
    // admin: todas | medico: las suyas | paciente: las suyas
    // Filtros: ?estado=pendiente&desde=2026-11-01&hasta=2026-11-30&per_page=15
    // ------------------------------------------------------------------
    public function index(Request $request)
    {
        $usuario = $request->user();

        $request->validate([
            'estado'   => ['nullable', Rule::in(['pendiente', 'confirmada', 'en_curso', 'cancelada', 'finalizada'])],
            'desde'    => 'nullable|date',
            'hasta'    => 'nullable|date|after_or_equal:desde',
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
              ->orderBy('fecha_hora', 'desc');

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
            'sala_video'            => 'cita-' . Str::uuid(), //se genera automaticamente
            'notas_medico_cifradas' => null,
        ]);

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
    // medico:   solo agrega 'notas_medico'
    // admin:    todo
    // ------------------------------------------------------------------
    public function update(Request $request, Citas $cita)
    {
        $usuario = $request->user();

        if ($denegado = $this->verificarAcceso($usuario, $cita)) {
            return $denegado;
        }

        //-------- MEDICO: solo notas --------
        if ($usuario->rol === 'medico') {
            $datos = $request->validate(['notas_medico' => 'required|string|max:5000']);

            $cita->update(['notas_medico_cifradas' => Crypt::encryptString($datos['notas_medico'])]);

            return response()->json([
                'mensaje' => 'Notas guardadas correctamente',
                'data'    => $this->transformar($cita->fresh(), $usuario),
            ]);
        }

        //-------- PACIENTE / ADMIN --------
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

        if (isset($datos['fecha_hora'])) {
            $datos['fecha_hora'] = $inicio;
            $datos['estado'] = 'pendiente'; //al reprogramar, el medico debe volver a confirmar
        }

        if (array_key_exists('notas_medico', $datos)) {
            $datos['notas_medico_cifradas'] = $datos['notas_medico'] ? Crypt::encryptString($datos['notas_medico']) : null;
            unset($datos['notas_medico']);
        }

        $cita->update($datos);

        return response()->json([
            'mensaje' => 'Cita actualizada correctamente',
            'data'    => $this->transformar($cita->fresh(), $usuario),
        ]);
    }

    // ------------------------------------------------------------------
    // PATCH /citas/{cita}/estado   (medico de la cita y admin)
    // ------------------------------------------------------------------
    public function cambiarEstado(Request $request, Citas $cita)
    {
        $usuario = $request->user();

        if ($denegado = $this->verificarAcceso($usuario, $cita)) {
            return $denegado;
        }

        $datos = $request->validate([
            'estado' => ['required', Rule::in(['confirmada', 'en_curso', 'cancelada', 'finalizada'])],
        ]);

        if (! in_array($datos['estado'], self::TRANSICIONES[$cita->estado], true)) {
            return response()->json([
                'mensaje' => "No se puede pasar de '{$cita->estado}' a '{$datos['estado']}'",
            ], 422);
        }

        $cita->update(['estado' => $datos['estado']]);

        return response()->json([
            'mensaje' => "Cita {$datos['estado']}",
            'data'    => $this->transformar($cita->fresh(), $usuario),
        ]);
    }

    // ------------------------------------------------------------------
    // PATCH /citas/{cita}/cancelar   (paciente, medico y admin; cada uno sobre las suyas)
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

        $cita->update(['estado' => 'cancelada']);

        return response()->json([
            'mensaje' => 'Cita cancelada correctamente',
            'data'    => $this->transformar($cita->fresh(), $usuario),
        ]);
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