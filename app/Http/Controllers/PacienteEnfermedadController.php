<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Enfermedad;
use App\Models\BitacoraAuditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PacienteEnfermedadController extends Controller
{
    //---------------- AUXILIARES ----------------

    //ver: admin, medico y el paciente dueño
    private function puedeVer(Request $request, Paciente $paciente): bool
    {
        $usuario = $request->user();

        return in_array($usuario->rol, ['admin', 'medico'], true)
            || (int) $paciente->usuario_id === (int) $usuario->id;
    }

    //escribir: admin y el paciente dueño (el medico no escribe)
    private function puedeEscribir(Request $request, Paciente $paciente): bool
    {
        $usuario = $request->user();

        return $usuario->rol === 'admin'
            || (int) $paciente->usuario_id === (int) $usuario->id;
    }

    private function sinPermiso(): JsonResponse
    {
        return response()->json(['mensaje' => 'No tienes permiso para acceder a las enfermedades de este paciente'], 403);
    }

    //---------------- ENDPOINTS (siguientes pasos) ----------------

    //GET /pacientes/{paciente}/enfermedades
public function index(Request $request, Paciente $paciente): JsonResponse
{
    if (! $this->puedeVer($request, $paciente)) {
        return $this->sinPermiso();
    }

    return response()->json([
        'paciente_id'  => $paciente->id,
        'enfermedades' => $paciente->enfermedades()->orderBy('nombre')->get(),
    ], 200);
}

//POST /pacientes/{paciente}/enfermedades
//Body: { "enfermedad_id": 1, "desde": "2020-05-10", "es_cronica": true }
public function store(Request $request, Paciente $paciente): JsonResponse
{
    if (! $this->puedeEscribir($request, $paciente)) {
        return $this->sinPermiso();
    }

    $datos = $request->validate([
        'enfermedad_id' => 'required|integer|exists:enfermedades,id',
        'desde'         => 'required|date|before_or_equal:today',
        'es_cronica'    => 'sometimes|boolean',
    ]);

    //la llave primaria es compuesta: si ya existe, attach lanzaria un error de SQL Server
    if ($paciente->enfermedades()->where('enfermedades.id', $datos['enfermedad_id'])->exists()) {
        return response()->json(['mensaje' => 'El paciente ya tiene registrada esta enfermedad'], 409);
    }

    $paciente->enfermedades()->attach($datos['enfermedad_id'], [
        'desde'      => $datos['desde'],
        'es_cronica' => $datos['es_cronica'] ?? false,
    ]);

    BitacoraAuditoria::registrar('crear', 'paciente_enfermedad', $paciente->id);

    return response()->json([
        'mensaje'      => 'Enfermedad registrada correctamente',
        'enfermedades' => $paciente->enfermedades()->orderBy('nombre')->get(),
    ], 201);
}

//PUT /pacientes/{paciente}/enfermedades/{enfermedad}
//Body: { "desde": "2019-01-01", "es_cronica": false }
public function update(Request $request, Paciente $paciente, Enfermedad $enfermedad): JsonResponse
{
    if (! $this->puedeEscribir($request, $paciente)) {
        return $this->sinPermiso();
    }

    if (! $paciente->enfermedades()->where('enfermedades.id', $enfermedad->id)->exists()) {
        return response()->json(['mensaje' => 'El paciente no tiene registrada esta enfermedad'], 404);
    }

    $datos = $request->validate([
        'desde'      => 'sometimes|date|before_or_equal:today',
        'es_cronica' => 'sometimes|boolean',
    ]);

    $paciente->enfermedades()->updateExistingPivot($enfermedad->id, $datos);

    BitacoraAuditoria::registrar('actualizar', 'paciente_enfermedad', $paciente->id);

    return response()->json(
        $paciente->enfermedades()->where('enfermedades.id', $enfermedad->id)->first(),
        200
    );
}

//DELETE /pacientes/{paciente}/enfermedades/{enfermedad}
public function destroy(Request $request, Paciente $paciente, Enfermedad $enfermedad): JsonResponse
{
    if (! $this->puedeEscribir($request, $paciente)) {
        return $this->sinPermiso();
    }

    if (! $paciente->enfermedades()->where('enfermedades.id', $enfermedad->id)->exists()) {
        return response()->json(['mensaje' => 'El paciente no tiene registrada esta enfermedad'], 404);
    }

    $paciente->enfermedades()->detach($enfermedad->id);

    BitacoraAuditoria::registrar('eliminar', 'paciente_enfermedad', $paciente->id);

    return response()->json(['mensaje' => 'Enfermedad removida correctamente'], 200);
}
}