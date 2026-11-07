<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Conversacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversacionController extends Controller
{
    /**
     * GET /conversaciones
     * Lista las conversaciones del usuario autenticado (el admin ve todas).
     */
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user();

        $query = Conversacion::with(['cita', 'participantes'])
            ->withCount('mensajes')
            ->orderByDesc('id');

        if ($usuario->rol !== 'admin') {
            $query->whereHas('participantes', function ($q) use ($usuario) {
                $q->where('participantes_conversacion.usuario_id', $usuario->id);
            });
        }

        return response()->json($query->paginate(15));
    }

    /**
     * POST /conversaciones
     * Body: { "cita_id": 1 }
     * Crea la conversación de una cita y agrega al paciente y al médico como participantes.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'cita_id' => 'required|integer|exists:citas,id',
        ]);

        $usuario = $request->user();
        $cita = Citas::with(['paciente', 'medico'])->findOrFail($datos['cita_id']);

        $usuarioPacienteId = $cita->paciente->usuario_id;
        $usuarioMedicoId   = $cita->medico->usuario_id;

        // Solo el paciente o el médico de la cita (o el admin) pueden abrir la conversación
        if ($usuario->rol !== 'admin'
            && ! in_array($usuario->id, [$usuarioPacienteId, $usuarioMedicoId], true)) {
            return response()->json([
                'mensaje' => 'No perteneces a esta cita',
            ], 403);
        }

        // Una cita solo puede tener una conversación (relación hasOne)
        $existente = Conversacion::where('cita_id', $cita->id)->first();
        if ($existente) {
            return response()->json([
                'mensaje'         => 'Esta cita ya tiene una conversacion',
                'conversacion_id' => $existente->id,
            ], 409);
        }

        $conversacion = DB::transaction(function () use ($cita, $usuarioPacienteId, $usuarioMedicoId) {
            $conversacion = Conversacion::create([
                'cita_id'   => $cita->id,
                'creado_en' => now(),
            ]);

            $conversacion->participantes()->attach([$usuarioPacienteId, $usuarioMedicoId]);

            return $conversacion;
        });

        return response()->json(
            $conversacion->load(['cita', 'participantes']),
            201
        );
    }

    /**
     * GET /conversaciones/{conversacion}
     * Solo participantes o admin.
     */
    public function show(Request $request, Conversacion $conversacion): JsonResponse
    {
        if (! $this->puedeAcceder($request->user(), $conversacion)) {
            return response()->json(['mensaje' => 'No tienes acceso a esta conversacion'], 403);
        }

        return response()->json(
            $conversacion->load(['cita', 'participantes'])->loadCount('mensajes')
        );
    }

    /**
     * GET /citas/{cita}/conversacion
     * Útil para la app: dado el id de la cita, obtener su conversación.
     */
    public function porCita(Request $request, Citas $cita): JsonResponse
    {
        $conversacion = Conversacion::where('cita_id', $cita->id)->first();

        if (! $conversacion) {
            return response()->json(['mensaje' => 'La cita no tiene conversacion'], 404);
        }

        if (! $this->puedeAcceder($request->user(), $conversacion)) {
            return response()->json(['mensaje' => 'No tienes acceso a esta conversacion'], 403);
        }

        return response()->json(
            $conversacion->load(['cita', 'participantes'])->loadCount('mensajes')
        );
    }

    /**
     * DELETE /conversaciones/{conversacion}
     * Solo admin. No se borra si ya tiene mensajes (historial médico).
     */
    public function destroy(Conversacion $conversacion): JsonResponse
    {
        if ($conversacion->mensajes()->exists()) {
            return response()->json([
                'mensaje' => 'No se puede eliminar una conversacion que ya tiene mensajes',
            ], 409);
        }

        DB::transaction(function () use ($conversacion) {
            $conversacion->participantes()->detach();
            $conversacion->delete();
        });

        return response()->json(['mensaje' => 'Conversacion eliminada']);
    }

    // ---------- helpers ----------

    private function puedeAcceder($usuario, Conversacion $conversacion): bool
    {
        if ($usuario->rol === 'admin') {
            return true;
        }

        return $conversacion->participantes()
            ->wherePivot('usuario_id', $usuario->id)
            ->exists();
    }
}