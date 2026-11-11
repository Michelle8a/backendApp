<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\SignosVitales;
use App\Models\BitacoraAuditoria;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SignosVitalesController extends Controller
{
    //---------------- AUXILIARES ----------------

    //ver: admin, medico y el paciente dueño
    private function puedeVer(Request $request, Paciente $paciente): bool
    {
        $usuario = $request->user();

        return in_array($usuario->rol, ['admin', 'medico'], true)
            || (int) $paciente->usuario_id === (int) $usuario->id;
    }

    //escribir: admin y el paciente dueño
    private function puedeEscribir(Request $request, Paciente $paciente): bool
    {
        $usuario = $request->user();

        return $usuario->rol === 'admin'
            || (int) $paciente->usuario_id === (int) $usuario->id;
    }

    private function sinPermiso(): JsonResponse
    {
        return response()->json(['mensaje' => 'No tienes permiso para acceder a los signos vitales de este paciente'], 403);
    }

    //---------------- ENDPOINTS (siguientes pasos) ----------------
    //GET /pacientes/{paciente}/signos_vitales?desde=2026-11-01&hasta=2026-11-30&per_page=15
public function index(Request $request, Paciente $paciente): JsonResponse
{
    if (! $this->puedeVer($request, $paciente)) {
        return $this->sinPermiso();
    }

    $filtros = $request->validate([
        'desde'    => 'sometimes|date',
        'hasta'    => 'sometimes|date|after_or_equal:desde',
        'per_page' => 'sometimes|integer|min:1|max:100',
    ]);

    $signos = $paciente->signosVitales()
        ->when($filtros['desde'] ?? null, fn ($q, $v) => $q->whereDate('medido_en', '>=', $v))
        ->when($filtros['hasta'] ?? null, fn ($q, $v) => $q->whereDate('medido_en', '<=', $v))
        ->orderByDesc('medido_en')
        ->paginate($filtros['per_page'] ?? 15);

    return response()->json($signos, 200);
}

//POST /pacientes/{paciente}/signos_vitales
//Body: { "presion_sistolica": 120, "presion_diastolica": 80, "peso_kg": 70.5,
//        "glucosa": 95.0, "temperatura": 36.6, "medido_en": "2026-11-10 08:30:00" }
public function store(Request $request, Paciente $paciente): JsonResponse
{
    if (! $this->puedeEscribir($request, $paciente)) {
        return $this->sinPermiso();
    }

    $datos = $request->validate([
        'presion_sistolica'  => 'required|integer|between:50,300',
        'presion_diastolica' => 'required|integer|between:30,200|lt:presion_sistolica',
        'peso_kg'            => 'required|numeric|between:1,500',
        'glucosa'            => 'required|numeric|between:10,1000',
        'temperatura'        => 'required|numeric|between:30,45',
        'medido_en'          => 'required|date|before_or_equal:now',
    ]);

    //paciente_id se asigna desde la relacion, nunca desde el body
    $signo = $paciente->signosVitales()->create($datos);

    BitacoraAuditoria::registrar('crear', 'signos_vitales', $signo->id);

    return response()->json([
        'mensaje' => 'Signos vitales registrados correctamente',
        'signos'  => $signo,
    ], 201);
}

//GET /signos_vitales/{signo}
public function show(Request $request, SignosVitales $signo): JsonResponse
{
    if (! $this->puedeVer($request, $signo->paciente)) {
        return $this->sinPermiso();
    }

    return response()->json($signo, 200);
}

//PUT /signos_vitales/{signo}
//Body: cualquier campo que quieras cambiar, por ejemplo { "peso_kg": 71 }
public function update(Request $request, SignosVitales $signo): JsonResponse
{
    if (! $this->puedeEscribir($request, $signo->paciente)) {
        return $this->sinPermiso();
    }

    $datos = $request->validate([
        'presion_sistolica'  => 'sometimes|integer|between:50,300',
        'presion_diastolica' => 'sometimes|integer|between:30,200',
        'peso_kg'            => 'sometimes|numeric|between:1,500',
        'glucosa'            => 'sometimes|numeric|between:10,1000',
        'temperatura'        => 'sometimes|numeric|between:30,45',
        'medido_en'          => 'sometimes|date|before_or_equal:now',
    ]);

    //con "sometimes" puede venir solo uno de los dos valores, asi que se compara contra el resultado final
    $sistolica  = $datos['presion_sistolica']  ?? $signo->presion_sistolica;
    $diastolica = $datos['presion_diastolica'] ?? $signo->presion_diastolica;

    if ($diastolica >= $sistolica) {
        return response()->json([
            'mensaje' => 'La presion diastolica debe ser menor que la sistolica',
        ], 422);
    }

    $signo->update($datos);

    BitacoraAuditoria::registrar('actualizar', 'signos_vitales', $signo->id);

    return response()->json($signo->fresh(), 200);
}

//DELETE /signos_vitales/{signo}
public function destroy(Request $request, SignosVitales $signo): JsonResponse
{
    if (! $this->puedeEscribir($request, $signo->paciente)) {
        return $this->sinPermiso();
    }

    $signo->delete();

    BitacoraAuditoria::registrar('eliminar', 'signos_vitales', $signo->id);

    return response()->json(['mensaje' => 'Signos vitales eliminados correctamente'], 200);
}
}




