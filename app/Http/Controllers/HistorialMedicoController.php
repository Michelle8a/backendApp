<?php

namespace App\Http\Controllers;

use App\Models\HistorialMedico;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Citas;
use App\Models\Receta;
use App\Models\BitacoraAuditoria;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;

class HistorialMedicoController extends Controller
{
    private const TIPOS = ['consulta', 'receta', 'resultado', 'signos_vitales'];

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
     * Un medico solo accede al historial de pacientes con los que tiene al menos una cita.
     */
    private function medicoAtiende(Medico $medico, int $pacienteId): bool
    {
        return Citas::where('medico_id', $medico->id)
            ->where('paciente_id', $pacienteId)
            ->exists();
    }

    /**
     * Lectura del historial de un paciente: admin | paciente (el suyo) | medico que lo atiende
     */
    private function puedeVerPaciente($usuario, int $pacienteId): bool
    {
        if ($usuario->rol === 'admin') {
            return true;
        }

        if ($usuario->rol === 'paciente') {
            $paciente = $this->pacienteDe($usuario);
            return $paciente && $paciente->id === $pacienteId;
        }

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            return $medico && $this->medicoAtiende($medico, $pacienteId);
        }

        return false;
    }

    /**
     * El diagnostico se guarda cifrado; hacia la app siempre sale descifrado
     * y nunca se expone la columna disgnostico_cifrado.
     */
    private function formatear(HistorialMedico $h): array
    {
        try {
            $diagnostico = Crypt::decryptString($h->disgnostico_cifrado);
        } catch (DecryptException $e) {
            $diagnostico = null;
        }

        return [
            'id'          => $h->id,
            'paciente_id' => $h->paciente_id,
            'cita_id'     => $h->cita_id,
            'receta_id'   => $h->receta_id,
            'creado_por'  => $h->creado_por,
            'tipo'        => $h->tipo,
            'diagnostico' => $diagnostico,
            'fecha'       => $h->fecha,
            'cita'        => $h->relationLoaded('cita') ? $h->cita : null,
            'receta'      => $h->relationLoaded('receta') ? $h->receta : null,
            'created_at'  => $h->created_at,
        ];
    }

    private function relaciones(): array
    {
        return ['cita:id,fecha_hora,tipo', 'receta:id,codigo,estado'];
    }

    //---------ENDPOINTS----------

    /**
     * GET /pacientes/{paciente}/historial_medico?tipo=consulta&desde=2026-01-01&hasta=2026-12-31&per_page=15
     */
    public function index(Request $request, Paciente $paciente)
    {
        if (! $this->puedeVerPaciente($request->user(), $paciente->id)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver este historial'], 403);
        }

        $request->validate([
            'tipo'     => ['nullable', Rule::in(self::TIPOS)],
            'desde'    => ['nullable', 'date'],
            'hasta'    => ['nullable', 'date', 'after_or_equal:desde'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $paciente->historialMedico()
            ->with($this->relaciones())
            ->orderByDesc('fecha');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('desde')) {
            $query->where('fecha', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->where('fecha', '<=', $request->hasta . ' 23:59:59');
        }

        $pagina = $query->paginate($request->input('per_page', 15));
        $pagina->getCollection()->transform(fn ($h) => $this->formatear($h));

        return response()->json($pagina);
    }

    /**
     * POST /pacientes/{paciente}/historial_medico
     * medico que atiende al paciente y admin
     */
    public function store(Request $request, Paciente $paciente)
    {
        $usuario = $request->user();
        $medico = null;

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            if (! $medico || ! $this->medicoAtiende($medico, $paciente->id)) {
                return response()->json([
                    'mensaje' => 'Solo puedes registrar historial de pacientes que atiendes',
                ], 403);
            }
        }

        $datos = $request->validate([
            'tipo'        => ['required', Rule::in(self::TIPOS)],
            'diagnostico' => ['required', 'string', 'max:10000'],
            'fecha'       => ['required', 'date', 'before_or_equal:now'],
            'cita_id'     => ['nullable', 'integer', 'exists:citas,id'],
            'receta_id'   => [
                Rule::requiredIf($request->input('tipo') === 'receta'),
                'nullable', 'integer', 'exists:recetas,id',
            ],
        ]);

        // La cita y la receta deben pertenecer al paciente (y al medico, si quien registra es medico)
        if (! empty($datos['cita_id'])) {
            $cita = Citas::find($datos['cita_id']);
            if ($cita->paciente_id !== $paciente->id || ($medico && $cita->medico_id !== $medico->id)) {
                return response()->json(['mensaje' => 'La cita no corresponde a este paciente o a tu agenda'], 422);
            }
        }

        if (! empty($datos['receta_id'])) {
            $receta = Receta::find($datos['receta_id']);
            if ($receta->paciente_id !== $paciente->id || ($medico && $receta->medico_id !== $medico->id)) {
                return response()->json(['mensaje' => 'La receta no corresponde a este paciente o no es tuya'], 422);
            }
        }

        $historial = HistorialMedico::create([
            'paciente_id'         => $paciente->id,
            'cita_id'             => $datos['cita_id'] ?? null,
            'receta_id'           => $datos['receta_id'] ?? null,
            'creado_por'          => $usuario->id,
            'tipo'                => $datos['tipo'],
            'disgnostico_cifrado' => Crypt::encryptString($datos['diagnostico']),
            'fecha'               => $datos['fecha'],
        ]);

        BitacoraAuditoria::registrar('crear', 'historial_medico', $historial->id);

        return response()->json([
            'mensaje'   => 'Registro agregado al historial medico',
            'historial' => $this->formatear($historial->load($this->relaciones())),
        ], 201);
    }

    /**
     * GET /historial_medico/{historial}
     */
    public function show(Request $request, HistorialMedico $historial)
    {
        if (! $this->puedeVerPaciente($request->user(), $historial->paciente_id)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver este registro'], 403);
        }

        return response()->json($this->formatear($historial->load($this->relaciones())));
    }

    /**
     * PUT /historial_medico/{historial}
     * Solo el medico que lo creo y el admin. No se puede cambiar paciente, cita ni receta.
     */
    public function update(Request $request, HistorialMedico $historial)
    {
        $usuario = $request->user();

        if ($usuario->rol !== 'admin' && $historial->creado_por !== $usuario->id) {
            return response()->json(['mensaje' => 'Solo quien creo el registro puede modificarlo'], 403);
        }

        $datos = $request->validate([
            'tipo'        => ['sometimes', Rule::in(self::TIPOS)],
            'diagnostico' => ['sometimes', 'string', 'max:10000'],
            'fecha'       => ['sometimes', 'date', 'before_or_equal:now'],
        ]);

        if (isset($datos['diagnostico'])) {
            $datos['disgnostico_cifrado'] = Crypt::encryptString($datos['diagnostico']);
            unset($datos['diagnostico']);
        }

        $historial->update($datos);

        BitacoraAuditoria::registrar('actualizar', 'historial_medico', $historial->id);

        return response()->json([
            'mensaje'   => 'Registro actualizado correctamente',
            'historial' => $this->formatear($historial->fresh($this->relaciones())),
        ]);
    }

    /**
     * DELETE /historial_medico/{historial}  (solo admin, lo controla la ruta)
     */
    public function destroy(HistorialMedico $historial)
    {
        $id = $historial->id;

        $historial->delete();

        BitacoraAuditoria::registrar('eliminar', 'historial_medico', $id);

        return response()->json(['mensaje' => 'Registro eliminado correctamente']);
    }
}