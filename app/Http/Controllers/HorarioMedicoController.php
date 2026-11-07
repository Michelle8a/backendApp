<?php

namespace App\Http\Controllers;

use App\Models\HorarioMedico;
use App\Models\Medico;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HorarioMedicoController extends Controller
{
    //dia_semana: 1 = Lunes ... 7 = Domingo (ISO-8601)
    private const DIAS = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miercoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sabado', 7 => 'Domingo',
    ];

    /**
     * El admin puede sobre cualquier medico; el medico solo sobre el suyo.
     */
    private function puedeGestionar(Request $request, Medico $medico): bool
    {
        $usuario = $request->user();

        return $usuario->rol === 'admin' || $medico->usuario_id === $usuario->id;
    }

    private function sinPermiso(): JsonResponse
    {
        return response()->json([
            'mensaje' => 'No tienes permiso para modificar los horarios de este medico',
        ], 403);
    }

    /**
     * Agrega el nombre del dia a la respuesta (util para la app).
     */
    private function formatear(HorarioMedico $h): array
    {
        return [
            'id'         => $h->id,
            'medico_id'  => $h->medico_id,
            'dia_semana' => $h->dia_semana,
            'dia_nombre' => self::DIAS[$h->dia_semana] ?? null,
            'hora_inicio' => substr($h->hora_inicio, 0, 5),
            'hora_fin'    => substr($h->hora_fin, 0, 5),
        ];
    }

    /**
     * Revisa si el rango se cruza con otro horario del mismo medico y dia.
     */
    private function haySolape(int $medicoId, int $dia, string $inicio, string $fin, ?int $ignorarId = null): bool
    {
        return HorarioMedico::where('medico_id', $medicoId)
            ->where('dia_semana', $dia)
            ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
            ->where('hora_inicio', '<', $fin)
            ->where('hora_fin', '>', $inicio)
            ->exists();
    }

    /**
     * GET /medicos/{medico}/horarios
     * Query opcional: ?dia_semana=1
     * Un medico no verificado solo lo ven el propio medico y el admin.
     */
    public function index(Request $request, Medico $medico): JsonResponse
    {
        if (! $medico->verificado && ! $this->puedeGestionar($request, $medico)) {
            return response()->json(['mensaje' => 'Medico no encontrado'], 404);
        }

        $horarios = $medico->horarios()
            ->when($request->filled('dia_semana'), fn ($q) => $q->where('dia_semana', (int) $request->dia_semana))
            ->orderBy('dia_semana')
            ->orderBy('hora_inicio')
            ->get()
            ->map(fn ($h) => $this->formatear($h));

        return response()->json([
            'medico_id' => $medico->id,
            'horarios'  => $horarios,
        ]);
    }

    /**
     * GET /horarios_medico/{horario}
     */
    public function show(Request $request, HorarioMedico $horario): JsonResponse
    {
        $medico = $horario->medico;

        if (! $medico->verificado && ! $this->puedeGestionar($request, $medico)) {
            return response()->json(['mensaje' => 'Horario no encontrado'], 404);
        }

        return response()->json($this->formatear($horario));
    }

    /**
     * POST /medicos/{medico}/horarios
     * Body: { "dia_semana": 1, "hora_inicio": "08:00", "hora_fin": "12:00" }
     */
    public function store(Request $request, Medico $medico): JsonResponse
    {
        if (! $this->puedeGestionar($request, $medico)) {
            return $this->sinPermiso();
        }

        $datos = $request->validate([
            'dia_semana'  => 'required|integer|between:1,7',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin'    => 'required|date_format:H:i|after:hora_inicio',
        ]);

        if ($this->haySolape($medico->id, $datos['dia_semana'], $datos['hora_inicio'], $datos['hora_fin'])) {
            return response()->json([
                'mensaje' => 'El horario se cruza con otro ya registrado para ese dia',
            ], 409);
        }

        $horario = $medico->horarios()->create($datos);

        return response()->json([
            'mensaje' => 'Horario creado correctamente',
            'horario' => $this->formatear($horario),
        ], 201);
    }

    /**
     * PUT /horarios_medico/{horario}
     * Body (todos opcionales): { "dia_semana": 2, "hora_inicio": "09:00", "hora_fin": "13:00" }
     */
    public function update(Request $request, HorarioMedico $horario): JsonResponse
    {
        if (! $this->puedeGestionar($request, $horario->medico)) {
            return $this->sinPermiso();
        }

        $datos = $request->validate([
            'dia_semana'  => 'sometimes|integer|between:1,7',
            'hora_inicio' => 'sometimes|date_format:H:i',
            'hora_fin'    => 'sometimes|date_format:H:i',
        ]);

        //valores finales (lo nuevo o lo que ya tenia) para validar el rango completo
        $dia    = $datos['dia_semana']  ?? $horario->dia_semana;
        $inicio = $datos['hora_inicio'] ?? substr($horario->hora_inicio, 0, 5);
        $fin    = $datos['hora_fin']    ?? substr($horario->hora_fin, 0, 5);

        if ($fin <= $inicio) {
            return response()->json([
                'mensaje' => 'La hora_fin debe ser posterior a la hora_inicio',
                'errors'  => ['hora_fin' => ['La hora_fin debe ser posterior a la hora_inicio']],
            ], 422);
        }

        if ($this->haySolape($horario->medico_id, (int) $dia, $inicio, $fin, $horario->id)) {
            return response()->json([
                'mensaje' => 'El horario se cruza con otro ya registrado para ese dia',
            ], 409);
        }

        $horario->update([
            'dia_semana'  => $dia,
            'hora_inicio' => $inicio,
            'hora_fin'    => $fin,
        ]);

        return response()->json([
            'mensaje' => 'Horario actualizado correctamente',
            'horario' => $this->formatear($horario->fresh()),
        ]);
    }

    /**
     * DELETE /horarios_medico/{horario}
     */
    public function destroy(Request $request, HorarioMedico $horario): JsonResponse
    {
        if (! $this->puedeGestionar($request, $horario->medico)) {
            return $this->sinPermiso();
        }

        $horario->delete();

        return response()->json(['mensaje' => 'Horario eliminado correctamente']);
    }
}