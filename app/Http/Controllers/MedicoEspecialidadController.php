<?php

namespace App\Http\Controllers;

use App\Models\Especialidad;
use App\Models\Medico;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MedicoEspecialidadController extends Controller
{
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
            'mensaje' => 'No tienes permiso para modificar las especialidades de este medico',
        ], 403);
    }

    /**
     * GET /medicos/{medico}/especialidades
     * Lista las especialidades de un medico.
     * Un medico no verificado solo lo ven el propio medico y el admin.
     */
    public function index(Request $request, Medico $medico): JsonResponse
    {
        if (! $medico->verificado && ! $this->puedeGestionar($request, $medico)) {
            return response()->json(['mensaje' => 'Medico no encontrado'], 404);
        }

        return response()->json([
            'medico_id'      => $medico->id,
            'especialidades' => $medico->especialidades()->orderBy('nombre')->get(),
        ]);
    }

    /**
     * GET /especialidades/{especialidad}/medicos
     * Lista los medicos verificados de una especialidad (para el buscador de la app).
     */
    public function medicosPorEspecialidad(Especialidad $especialidad): JsonResponse
    {
        $medicos = $especialidad->medicos()
            ->where('verificado', true)
            ->with('especialidades:id,nombre')
            ->orderByDesc('calificacion_promedio')
            ->paginate(15);

        return response()->json($medicos);
    }

    /**
     * POST /medicos/{medico}/especialidades
     * Asigna UNA especialidad al medico.
     * Body: { "especialidad_id": 1 }
     */
    public function store(Request $request, Medico $medico): JsonResponse
    {
        if (! $this->puedeGestionar($request, $medico)) {
            return $this->sinPermiso();
        }

        $datos = $request->validate([
            'especialidad_id' => 'required|integer|exists:especialidades,id',
        ]);

        if ($medico->especialidades()->where('especialidades.id', $datos['especialidad_id'])->exists()) {
            return response()->json([
                'mensaje' => 'El medico ya tiene asignada esta especialidad',
            ], 409);
        }

        $medico->especialidades()->attach($datos['especialidad_id']);

        return response()->json([
            'mensaje'        => 'Especialidad asignada correctamente',
            'especialidades' => $medico->especialidades()->orderBy('nombre')->get(),
        ], 201);
    }

    /**
     * PUT /medicos/{medico}/especialidades
     * Reemplaza TODAS las especialidades del medico.
     * Body: { "especialidades": [1, 3, 5] }
     */
    public function sync(Request $request, Medico $medico): JsonResponse
    {
        if (! $this->puedeGestionar($request, $medico)) {
            return $this->sinPermiso();
        }

        $datos = $request->validate([
            'especialidades'   => 'required|array|min:1',
            'especialidades.*' => 'integer|distinct|exists:especialidades,id',
        ]);

        $medico->especialidades()->sync($datos['especialidades']);

        return response()->json([
            'mensaje'        => 'Especialidades actualizadas correctamente',
            'especialidades' => $medico->especialidades()->orderBy('nombre')->get(),
        ]);
    }

    /**
     * DELETE /medicos/{medico}/especialidades/{especialidad}
     * Quita una especialidad del medico.
     */
    public function destroy(Request $request, Medico $medico, Especialidad $especialidad): JsonResponse
    {
        if (! $this->puedeGestionar($request, $medico)) {
            return $this->sinPermiso();
        }

        if (! $medico->especialidades()->where('especialidades.id', $especialidad->id)->exists()) {
            return response()->json([
                'mensaje' => 'El medico no tiene asignada esta especialidad',
            ], 404);
        }

        $medico->especialidades()->detach($especialidad->id);

        return response()->json(['mensaje' => 'Especialidad removida correctamente']);
    }
}