<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use App\Models\Paciente;
use App\Services\DisponibilidadService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MedicoDisponibilidadController extends Controller
{
    /**
     * GET /medicos/{medico}/disponibilidad?desde=2026-11-13&dias=14&duracion=30
     * Devuelve solo los dias con horas libres:
     * { "medico_id": 4, "duracion_min": 30, "dias": [ { "fecha": "2026-11-13", "horas": ["09:00","09:30"] } ] }
     */
    public function index(Request $request, Medico $medico, DisponibilidadService $servicio): JsonResponse
    {
        $usuario = $request->user();

        $esDuenoOAdmin = $usuario->rol === 'admin' || (int) $medico->usuario_id === (int) $usuario->id;
        if (! $medico->verificado && ! $esDuenoOAdmin) {
            return response()->json(['mensaje' => 'Medico no encontrado'], 404);
        }

        $datos = $request->validate([
            'desde'    => 'nullable|date_format:Y-m-d',
            'dias'     => 'nullable|integer|min:1|max:30',
            'duracion' => 'nullable|integer|min:15|max:120',
        ]);

        $tz       = config('app.timezone');
        $desde    = isset($datos['desde']) ? Carbon::createFromFormat('Y-m-d', $datos['desde'], $tz) : Carbon::now($tz);
        $dias     = (int) ($datos['dias'] ?? 14);
        $duracion = (int) ($datos['duracion'] ?? DisponibilidadService::DURACION_DEFAULT);

        // Si quien consulta es un paciente, tambien se ocultan las horas en que ya tiene otra cita
        $pacienteId = $usuario->rol === 'paciente'
            ? Paciente::where('usuario_id', $usuario->id)->value('id')
            : null;

        return response()->json([
            'medico_id'    => $medico->id,
            'duracion_min' => $duracion,
            'dias'         => $servicio->horasLibres($medico, $desde, $dias, $duracion, $pacienteId),
        ]);
    }
}