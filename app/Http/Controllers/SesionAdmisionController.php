<?php

namespace App\Http\Controllers;

use App\Models\Citas;
use App\Models\Paciente;
use App\Models\SesionAdmision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SesionAdmisionController extends Controller
{
    //relaciones que el cliente puede pedir con ?con=respuestas,bandera_roja,archivos_adjuntos
    private const RELACIONES_PERMITIDAS = ['respuestas', 'bandera_roja', 'archivos_adjuntos'];

    /**
     * Listar las sesiones de admision de un paciente.
     * GET /pacientes/{paciente}/sesiones_admision?estado=&nivel_urgencia=
     *
     * - paciente: solo las suyas
     * - medico: solo las de sus propias citas
     * - admin: todas
     */
    public function index(Request $request, Paciente $paciente): JsonResponse
    {
        $usuario = $request->user();

        if ($usuario->rol === 'paciente' && (int) $paciente->usuario_id !== (int) $usuario->id) {
            return $this->sinPermiso();
        }

        $request->validate([
            'estado'         => 'sometimes|in:en_progreso,completada,abandonada',
            'nivel_urgencia' => 'sometimes|in:baja,media,alta,emergencia',
        ]);

        $query = $paciente->sesionesAdmision()->with('cita:id,fecha_hora,tipo,estado,motivo,medico_id');

        if ($usuario->rol === 'medico') {
            $query->whereHas('cita.medico', fn ($q) => $q->where('usuario_id', $usuario->id));
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('nivel_urgencia')) {
            $query->where('nivel_urgencia', $request->nivel_urgencia);
        }

        return response()->json($query->orderByDesc('id')->paginate(15));
    }

    /**
     * Crear la sesion de admision de una cita (una sola por cita).
     * POST /citas/{cita}/sesion_admision
     * Siempre nace en_progreso; el paciente_id se toma de la cita.
     */
    public function store(Request $request, Citas $cita): JsonResponse
    {
        $usuario = $request->user();

        if ($usuario->rol === 'paciente' && (int) $cita->paciente?->usuario_id !== (int) $usuario->id) {
            return $this->sinPermiso();
        }

        if ($cita->sesion_admision()->exists()) {
            return response()->json([
                'mensaje' => 'Esta cita ya tiene una sesion de admision',
                'data'    => $cita->sesion_admision,
            ], 409);
        }

        $datos = $request->validate([
            'nivel_urgencia'      => 'nullable|in:baja,media,alta,emergencia',
            'resumen_para_medico' => 'nullable|string|max:5000',
        ]);

        $sesion = SesionAdmision::create([
            'cita_id'             => $cita->id,
            'paciente_id'         => $cita->paciente_id,
            'estado'              => 'en_progreso',
            'nivel_urgencia'      => $datos['nivel_urgencia'] ?? null,
            'resumen_para_medico' => $datos['resumen_para_medico'] ?? null,
            'completada_en'       => null,
        ]);

        return response()->json([
            'mensaje' => 'Sesion de admision creada correctamente',
            'data'    => $sesion,
        ], 201);
    }

    /**
     * Ver la sesion de admision de una cita (atajo para la pantalla de la cita).
     * GET /citas/{cita}/sesion_admision
     */
    public function porCita(Request $request, Citas $cita): JsonResponse
    {
        $sesion = $cita->sesion_admision;

        if (! $sesion) {
            return response()->json(['mensaje' => 'Esta cita no tiene sesion de admision'], 404);
        }

        return $this->show($request, $sesion);
    }

    /**
     * Ver una sesion.
     * GET /sesiones_admision/{sesion}?con=respuestas,bandera_roja,archivos_adjuntos
     */
    public function show(Request $request, SesionAdmision $sesion): JsonResponse
    {
        if (! $this->puedeVer($request, $sesion)) {
            return $this->sinPermiso();
        }

        $sesion->load('cita');

        $pedidas = array_intersect(
            self::RELACIONES_PERMITIDAS,
            array_filter(explode(',', (string) $request->query('con')))
        );
        if ($pedidas) {
            $sesion->load($pedidas);
        }

        return response()->json(['data' => $sesion]);
    }

    /**
     * Actualizar estado / urgencia / resumen.
     * PUT /sesiones_admision/{sesion}
     *
     * - completada_en lo pone el servidor, no el cliente.
     * - Una sesion cerrada (completada/abandonada) solo la puede modificar un admin.
     */
    public function update(Request $request, SesionAdmision $sesion): JsonResponse
    {
        $usuario = $request->user();

        if (! $this->puedeVer($request, $sesion)) {
            return $this->sinPermiso();
        }

        if ($sesion->estado !== 'en_progreso' && $usuario->rol !== 'admin') {
            return response()->json([
                'mensaje' => 'La sesion ya esta cerrada y no se puede modificar',
            ], 409);
        }

        $datos = $request->validate([
            'estado'              => 'sometimes|in:en_progreso,completada,abandonada',
            'nivel_urgencia'      => 'sometimes|nullable|in:baja,media,alta,emergencia',
            'resumen_para_medico' => 'sometimes|nullable|string|max:5000',
        ]);

        $estadoFinal  = $datos['estado'] ?? $sesion->estado;
        $urgenciaFin  = array_key_exists('nivel_urgencia', $datos) ? $datos['nivel_urgencia'] : $sesion->nivel_urgencia;
        $resumenFinal = array_key_exists('resumen_para_medico', $datos) ? $datos['resumen_para_medico'] : $sesion->resumen_para_medico;

        //para completar, la sesion debe tener urgencia y resumen
        if ($estadoFinal === 'completada') {
            $errores = [];
            if (! $urgenciaFin) {
                $errores['nivel_urgencia'] = 'Es obligatorio para completar la sesion';
            }
            if (! $resumenFinal) {
                $errores['resumen_para_medico'] = 'Es obligatorio para completar la sesion';
            }
            if ($errores) {
                throw ValidationException::withMessages($errores);
            }
        }

        if ($estadoFinal === 'completada') {
            $datos['completada_en'] = $sesion->completada_en ?? now();
        } else {
            $datos['completada_en'] = null;
        }

        $sesion->update($datos);

        return response()->json([
            'mensaje' => 'Sesion de admision actualizada correctamente',
            'data'    => $sesion->fresh(),
        ]);
    }

    /**
     * Eliminar una sesion (solo admin, por ruta).
     * DELETE /sesiones_admision/{sesion}
     */
    public function destroy(SesionAdmision $sesion): JsonResponse
    {
        $sesion->delete();

        return response()->json(['mensaje' => 'Sesion de admision eliminada correctamente']);
    }


    /**
     * admin: todo | paciente: solo la suya | medico: solo si la cita es suya.
     * 
     */
    private function puedeVer(Request $request, SesionAdmision $sesion): bool
    {
        $usuario = $request->user();

        return match ($usuario->rol) {
            'admin'    => true,
            'paciente' => (int) $sesion->paciente?->usuario_id === (int) $usuario->id,
            'medico'   => (int) $sesion->cita?->medico?->usuario_id === (int) $usuario->id,
            default    => false,
        };
    }

    private function sinPermiso(): JsonResponse
    {
        return response()->json([
            'mensaje' => 'No tienes permiso para acceder a esta sesion de admision',
        ], 403);
    }
}