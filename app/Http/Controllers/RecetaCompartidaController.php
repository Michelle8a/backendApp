<?php

namespace App\Http\Controllers;

use App\Models\Farmacia;
use App\Models\Receta;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\BitacoraAuditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RecetaCompartidaController extends Controller
{
    private const ESTADOS = ['vigente', 'dispensada', 'vencida'];

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
     * Lectura: admin | medico emisor | paciente dueño
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
     * Compartir o revocar: admin | paciente dueño (el consentimiento es suyo)
     */
    private function puedeGestionar($usuario, Receta $receta): bool
    {
        if ($usuario->rol === 'admin') {
            return true;
        }

        if ($usuario->rol === 'paciente') {
            $paciente = $this->pacienteDe($usuario);
            return $paciente && $paciente->id === $receta->paciente_id;
        }

        return false;
    }

    private function estaCompartida(Receta $receta, int $farmaciaId): bool
    {
        return $receta->farmacias_compartidas()->where('farmacias.id', $farmaciaId)->exists();
    }

    //---------ENDPOINTS----------

    /**
     * GET /recetas/{receta}/farmacias
     * Farmacias con las que esta compartida la receta.
     */
    public function index(Request $request, Receta $receta)
    {
        if (! $this->puedeVer($request->user(), $receta)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver esta receta'], 403);
        }

        $farmacias = $receta->farmacias_compartidas()
            ->get()
            ->map(fn ($f) => [
                'farmacia_id'   => $f->id,
                'nombre'        => $f->nombre,
                'direccion'     => $f->direccion,
                'telefono'      => $f->telefono,
                'correo'        => $f->correo,
                'latitud'       => $f->latitud,
                'longitud'      => $f->longitud,
                'compartida_en' => $f->pivot->created_at,
            ]);

        return response()->json($farmacias);
    }

    /**
     * POST /recetas/{receta}/farmacias
     * paciente dueño y admin. Solo recetas vigentes.
     */
    public function store(Request $request, Receta $receta)
    {
        if (! $this->puedeGestionar($request->user(), $receta)) {
            return response()->json(['mensaje' => 'No puedes compartir esta receta'], 403);
        }

        if ($receta->estado !== 'vigente') {
            return response()->json([
                'mensaje' => 'Solo se pueden compartir recetas vigentes',
            ], 422);
        }

        $datos = $request->validate([
            'farmacia_id' => ['required', 'integer', 'exists:farmacias,id'],
        ]);

        if ($this->estaCompartida($receta, $datos['farmacia_id'])) {
            return response()->json([
                'mensaje' => 'La receta ya esta compartida con esa farmacia',
            ], 422);
        }

        $receta->farmacias_compartidas()->attach($datos['farmacia_id'], [
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // la tabla pivote tiene llave compuesta, se registra el id de la receta
        BitacoraAuditoria::registrar('crear', 'receta_compartida', $receta->id);

        return response()->json([
            'mensaje'     => 'Receta compartida con la farmacia correctamente',
            'farmacia_id' => $datos['farmacia_id'],
        ], 201);
    }

    /**
     * DELETE /recetas/{receta}/farmacias/{farmacia}
     * Revocar el acceso. paciente dueño y admin.
     */
    public function destroy(Request $request, Receta $receta, Farmacia $farmacia)
    {
        if (! $this->puedeGestionar($request->user(), $receta)) {
            return response()->json(['mensaje' => 'No puedes modificar esta receta'], 403);
        }

        if (! $this->estaCompartida($receta, $farmacia->id)) {
            return response()->json([
                'mensaje' => 'La receta no esta compartida con esa farmacia',
            ], 404);
        }

        $receta->farmacias_compartidas()->detach($farmacia->id);

        BitacoraAuditoria::registrar('eliminar', 'receta_compartida', $receta->id);

        return response()->json(['mensaje' => 'Se dejo de compartir la receta con la farmacia']);
    }

    /**
     * GET /farmacias/{farmacia}/recetas?estado=vigente&per_page=15
     * Recetas compartidas con una farmacia. Solo admin (las farmacias no tienen usuario).
     */
    public function recetasDeFarmacia(Request $request, Farmacia $farmacia)
    {
        $request->validate([
            'estado'   => ['nullable', Rule::in(self::ESTADOS)],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $farmacia->receta_compartida()
            ->with(['paciente:id,nombres,apellidos', 'detalles'])
            ->orderByDesc('recetas_compartidas.created_at');

        if ($request->filled('estado')) {
            $query->where('recetas.estado', $request->estado);
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }
}