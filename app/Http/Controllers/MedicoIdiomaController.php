<?php

namespace App\Http\Controllers;

use App\Models\Idioma;
use App\Models\Medico;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MedicoIdiomaController extends Controller
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
            'mensaje' => 'No tienes permiso para modificar los idiomas de este medico',
        ], 403);
    }

    /**
     * GET /medicos/{medico}/idiomas
     * Lista los idiomas de un medico.
     * Un medico no verificado solo lo ven el propio medico y el admin.
     */
    public function index(Request $request, Medico $medico): JsonResponse
    {
        if (! $medico->verificado && ! $this->puedeGestionar($request, $medico)) {
            return response()->json(['mensaje' => 'Medico no encontrado'], 404);
        }

        return response()->json([
            'medico_id' => $medico->id,
            'idiomas'   => $medico->idiomas()->orderBy('nombre')->get(),
        ]);
    }

    /**
     * GET /idiomas/{idioma}/medicos
     * Lista los medicos verificados que hablan un idioma (filtro del buscador de la app).
     */
    public function medicosPorIdioma(Idioma $idioma): JsonResponse
    {
        $medicos = $idioma->medicos()
            ->where('verificado', true)
            ->with('idiomas:id,nombre,codigo')
            ->orderByDesc('calificacion_promedio')
            ->paginate(15);

        return response()->json($medicos);
    }

    /**
     * POST /medicos/{medico}/idiomas
     * Asigna UN idioma al medico.
     * Body: { "idioma_id": 1 }
     */
    public function store(Request $request, Medico $medico): JsonResponse
    {
        if (! $this->puedeGestionar($request, $medico)) {
            return $this->sinPermiso();
        }

        $datos = $request->validate([
            'idioma_id' => 'required|integer|exists:idiomas,id',
        ]);

        if ($medico->idiomas()->where('idiomas.id', $datos['idioma_id'])->exists()) {
            return response()->json([
                'mensaje' => 'El medico ya tiene asignado este idioma',
            ], 409);
        }

        $medico->idiomas()->attach($datos['idioma_id']);

        return response()->json([
            'mensaje' => 'Idioma asignado correctamente',
            'idiomas' => $medico->idiomas()->orderBy('nombre')->get(),
        ], 201);
    }

    /**
     * PUT /medicos/{medico}/idiomas
     * Reemplaza TODOS los idiomas del medico (sync).
     * Body: { "idiomas": [1, 2] }
     */
    public function sync(Request $request, Medico $medico): JsonResponse
    {
        if (! $this->puedeGestionar($request, $medico)) {
            return $this->sinPermiso();
        }

        $datos = $request->validate([
            'idiomas'   => 'required|array|min:1',
            'idiomas.*' => 'integer|distinct|exists:idiomas,id',
        ]);

        $medico->idiomas()->sync($datos['idiomas']);

        return response()->json([
            'mensaje' => 'Idiomas actualizados correctamente',
            'idiomas' => $medico->idiomas()->orderBy('nombre')->get(),
        ]);
    }

    /**
     * DELETE /medicos/{medico}/idiomas/{idioma}
     * Quita un idioma del medico.
     */
    public function destroy(Request $request, Medico $medico, Idioma $idioma): JsonResponse
    {
        if (! $this->puedeGestionar($request, $medico)) {
            return $this->sinPermiso();
        }

        if (! $medico->idiomas()->where('idiomas.id', $idioma->id)->exists()) {
            return response()->json([
                'mensaje' => 'El medico no tiene asignado este idioma',
            ], 404);
        }

        $medico->idiomas()->detach($idioma->id);

        return response()->json(['mensaje' => 'Idioma removido correctamente']);
    }
}