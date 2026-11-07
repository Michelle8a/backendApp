<?php

namespace App\Http\Controllers;

use App\Models\DetalleReceta;
use App\Models\Receta;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\BitacoraAuditoria;
use Illuminate\Http\Request;

class DetalleRecetaController extends Controller
{
    //---------HELPERS----------

    private function pacienteDe($usuario): ?Paciente
    {
        return Paciente::where('usuario_id', $usuario->id)->first();
    }

    private function medicoDe($usuario): ?Medico
    {
        return Medico::where('usuario_id', $usuario->id)->first();
    }

    /**
     * Lectura: admin | medico que emitio la receta | paciente dueño de la receta
     */
    private function puedeVer($usuario, Receta $receta): bool
    {
        if ($usuario->rol === 'admin') {
            return true;
        }

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            return $medico && $medico->id === $receta->medico_id;
        }

        if ($usuario->rol === 'paciente') {
            $paciente = $this->pacienteDe($usuario);
            return $paciente && $paciente->id === $receta->paciente_id;
        }

        return false;
    }

    /**
     * Escritura: admin siempre | medico emisor solo mientras la receta este vigente
     */
    private function puedeEscribir($usuario, Receta $receta): bool
    {
        if ($usuario->rol === 'admin') {
            return true;
        }

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            return $medico
                && $medico->id === $receta->medico_id
                && $receta->estado === 'vigente';
        }

        return false;
    }

    //---------ENDPOINTS----------

    /**
     * GET /recetas/{receta}/detalles
     */
    public function index(Request $request, Receta $receta)
    {
        if (! $this->puedeVer($request->user(), $receta)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver los detalles de esta receta'], 403);
        }

        return response()->json($receta->detalles()->orderBy('id')->get());
    }

    /**
     * POST /recetas/{receta}/detalles
     * medico emisor (receta vigente) y admin
     */
    public function store(Request $request, Receta $receta)
    {
        if (! $this->puedeEscribir($request->user(), $receta)) {
            return response()->json([
                'mensaje' => 'No puedes agregar medicamentos a esta receta (no es tuya o ya no esta vigente)',
            ], 403);
        }

        $datos = $request->validate([
            'medicamento'  => ['required', 'string', 'max:150'],
            'dosis'        => ['required', 'string', 'max:80'],
            'frecuencia'   => ['required', 'string', 'max:80'],
            'duracion'     => ['required', 'string', 'max:80'],
            'indicaciones' => ['required', 'string', 'max:2000'],
        ]);

        $detalle = $receta->detalles()->create($datos);

        BitacoraAuditoria::registrar('crear', 'detalle_receta', $detalle->id);

        return response()->json([
            'mensaje' => 'Medicamento agregado a la receta',
            'detalle' => $detalle,
        ], 201);
    }

    /**
     * GET /detalles_receta/{detalle}
     */
    public function show(Request $request, DetalleReceta $detalle)
    {
        if (! $this->puedeVer($request->user(), $detalle->receta)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver este detalle'], 403);
        }

        return response()->json($detalle);
    }

    /**
     * PUT /detalles_receta/{detalle}
     * medico emisor (receta vigente) y admin. No se puede mover a otra receta.
     */
    public function update(Request $request, DetalleReceta $detalle)
    {
        if (! $this->puedeEscribir($request->user(), $detalle->receta)) {
            return response()->json([
                'mensaje' => 'No puedes modificar este detalle (la receta no es tuya o ya no esta vigente)',
            ], 403);
        }

        $datos = $request->validate([
            'medicamento'  => ['sometimes', 'string', 'max:150'],
            'dosis'        => ['sometimes', 'string', 'max:80'],
            'frecuencia'   => ['sometimes', 'string', 'max:80'],
            'duracion'     => ['sometimes', 'string', 'max:80'],
            'indicaciones' => ['sometimes', 'string', 'max:2000'],
        ]);

        $detalle->update($datos);

        BitacoraAuditoria::registrar('actualizar', 'detalle_receta', $detalle->id);

        return response()->json([
            'mensaje' => 'Detalle actualizado correctamente',
            'detalle' => $detalle->fresh(),
        ]);
    }

    /**
     * DELETE /detalles_receta/{detalle}
     * medico emisor (receta vigente) y admin
     */
    public function destroy(Request $request, DetalleReceta $detalle)
    {
        if (! $this->puedeEscribir($request->user(), $detalle->receta)) {
            return response()->json([
                'mensaje' => 'No puedes eliminar este detalle (la receta no es tuya o ya no esta vigente)',
            ], 403);
        }

        $id = $detalle->id;

        $detalle->delete();

        BitacoraAuditoria::registrar('eliminar', 'detalle_receta', $id);

        return response()->json(['mensaje' => 'Detalle eliminado correctamente']);
    }
}