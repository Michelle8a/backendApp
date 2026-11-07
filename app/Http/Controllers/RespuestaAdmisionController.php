<?php

namespace App\Http\Controllers;

use App\Models\RespuestaAdmision;
use App\Models\SesionAdmision;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\BitacoraAuditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RespuestaAdmisionController extends Controller
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
     * admin: todo | paciente: solo sus sesiones | medico: solo sesiones de sus citas
     */
    private function puedeVerSesion($usuario, SesionAdmision $sesion): bool
    {
        if ($usuario->rol === 'admin') {
            return true;
        }

        if ($usuario->rol === 'paciente') {
            $paciente = $this->pacienteDe($usuario);
            return $paciente && $paciente->id === $sesion->paciente_id;
        }

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            $sesion->loadMissing('cita');
            return $medico && $sesion->cita && $sesion->cita->medico_id === $medico->id;
        }

        return false;
    }

    /**
     * Escritura: admin siempre | paciente solo en sus sesiones aun no completadas
     */
    private function puedeEscribirSesion($usuario, SesionAdmision $sesion): bool
    {
        if ($usuario->rol === 'admin') {
            return true;
        }

        if ($usuario->rol === 'paciente') {
            $paciente = $this->pacienteDe($usuario);
            return $paciente
                && $paciente->id === $sesion->paciente_id
                && is_null($sesion->completada_en);
        }

        return false;
    }

    private function reglas(SesionAdmision $sesion, bool $esStore, ?RespuestaAdmision $actual = null): array
    {
        $unicaClave = Rule::unique('respuestas_admision', 'clave')
            ->where('sesion_id', $sesion->id);

        if ($actual) {
            $unicaClave->ignore($actual->id);
        }

        $presencia = $esStore ? 'required' : 'sometimes';

        return [
            'clave'         => [$presencia, 'string', 'max:50', $unicaClave],
            'pregunta'      => [$presencia, 'string', 'max:255'],
            'respuesta'     => [$presencia, 'string'],
            'respondida_en' => ['nullable', 'date'],
        ];
    }

    //---------ENDPOINTS----------

    /**
     * GET /sesiones_admision/{sesion}/respuestas
     */
    public function index(Request $request, SesionAdmision $sesion)
    {
        if (! $this->puedeVerSesion($request->user(), $sesion)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver estas respuestas'], 403);
        }

        $respuestas = $sesion->respuestas()->orderBy('respondida_en')->orderBy('id')->get();

        return response()->json($respuestas);
    }

    /**
     * POST /sesiones_admision/{sesion}/respuestas
     */
    public function store(Request $request, SesionAdmision $sesion)
    {
        if (! $this->puedeEscribirSesion($request->user(), $sesion)) {
            return response()->json([
                'mensaje' => 'No puedes agregar respuestas a esta sesion (no es tuya o ya fue completada)',
            ], 403);
        }

        $datos = $request->validate($this->reglas($sesion, true));

        $datos['sesion_id'] = $sesion->id;
        $datos['respondida_en'] = $datos['respondida_en'] ?? now();

        $respuesta = RespuestaAdmision::create($datos);

        BitacoraAuditoria::registrar('crear', 'respuesta_admision', $respuesta->id);

        return response()->json([
            'mensaje'   => 'Respuesta registrada correctamente',
            'respuesta' => $respuesta,
        ], 201);
    }

    /**
     * GET /respuestas_admision/{respuesta}
     */
    public function show(Request $request, RespuestaAdmision $respuesta)
    {
        if (! $this->puedeVerSesion($request->user(), $respuesta->sesion)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver esta respuesta'], 403);
        }

        return response()->json($respuesta);
    }

    /**
     * PUT /respuestas_admision/{respuesta}
     * paciente (su sesion, no completada) y admin. No se puede mover de sesion.
     */
    public function update(Request $request, RespuestaAdmision $respuesta)
    {
        $sesion = $respuesta->sesion;

        if (! $this->puedeEscribirSesion($request->user(), $sesion)) {
            return response()->json([
                'mensaje' => 'No puedes modificar esta respuesta (no es tuya o la sesion ya fue completada)',
            ], 403);
        }

        $datos = $request->validate($this->reglas($sesion, false, $respuesta));

        $respuesta->update($datos);

        BitacoraAuditoria::registrar('actualizar', 'respuesta_admision', $respuesta->id);

        return response()->json([
            'mensaje'   => 'Respuesta actualizada correctamente',
            'respuesta' => $respuesta->fresh(),
        ]);
    }

    /**
     * DELETE /respuestas_admision/{respuesta}  (solo admin, lo controla la ruta)
     */
    public function destroy(RespuestaAdmision $respuesta)
    {
        $id = $respuesta->id;

        $respuesta->delete();

        BitacoraAuditoria::registrar('eliminar', 'respuesta_admision', $id);

        return response()->json(['mensaje' => 'Respuesta eliminada correctamente']);
    }
}