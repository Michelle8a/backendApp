<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Citas;
use App\Models\BitacoraAuditoria;
use App\Services\Notificador;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

//RECETAS PDF
use Barryvdh\DomPDF\Facade\Pdf;

class RecetaController extends Controller
{
    private const ESTADOS = ['vigente', 'dispensada', 'vencida'];

    private function pacienteDe($usuario): ?Paciente
    {
        return Paciente::where('usuario_id', $usuario->id)->first();
    }

    private function medicoDe($usuario): ?Medico
    {
        return Medico::where('usuario_id', $usuario->id)->first();
    }

    /**
     * admin: todo | medico: solo las que emitio | paciente: solo las suyas
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

    private function generarCodigo(): string
    {
        do {
            $codigo = 'REC-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (Receta::where('codigo', $codigo)->exists());

        return $codigo;
    }

    //---------ENDPOINTS----------

    /**
     * GET /recetas?estado=vigente&paciente_id=1&per_page=15
     */
    public function index(Request $request)
    {
        $usuario = $request->user();

        $request->validate([
            'estado'      => ['nullable', Rule::in(self::ESTADOS)],
            'paciente_id' => ['nullable', 'integer'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Receta::with([
        'paciente:id,nombres,apellidos',
        'medico:id,nombres,apellidos',
        'medico.especialidades:especialidades.id,especialidades.nombre',
    ])
    ->orderByDesc('created_at');

        if ($usuario->rol === 'paciente') {
            $paciente = $this->pacienteDe($usuario);
            if (! $paciente) {
                return response()->json(['mensaje' => 'El usuario no tiene perfil de paciente'], 404);
            }
            $query->where('paciente_id', $paciente->id);
        } elseif ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            if (! $medico) {
                return response()->json(['mensaje' => 'El usuario no tiene perfil de medico'], 404);
            }
            $query->where('medico_id', $medico->id);

            if ($request->filled('paciente_id')) {
                $query->where('paciente_id', $request->paciente_id);
            }
        } elseif ($request->filled('paciente_id')) { // admin
            $query->where('paciente_id', $request->paciente_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    /**
     * POST /recetas
     * medico: el medico_id se toma de su sesion | admin: debe enviar medico_id
     */
        public function store(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'paciente_id'   => ['required', 'integer', 'exists:pacientes,id'],
            'medico_id'     => [$usuario->rol === 'admin' ? 'required' : 'prohibited', 'integer', 'exists:medicos,id'],
            'cita_id'       => ['required', 'integer', 'exists:citas,id', Rule::unique('recetas', 'cita_id')],
            'estado'        => ['nullable', Rule::in(self::ESTADOS)],
            'firma_digital' => ['nullable', 'string', 'max:255'],
            'pdf_url'       => ['nullable', 'string', 'max:255'],

            'detalles'                => ['required', 'array', 'min:1', 'max:20'],
            'detalles.*.medicamento'  => ['required', 'string', 'max:150'],
            'detalles.*.dosis'        => ['required', 'string', 'max:80'],
            'detalles.*.frecuencia'   => ['required', 'string', 'max:80'],
            'detalles.*.duracion'     => ['required', 'string', 'max:80'],
            'detalles.*.indicaciones' => ['required', 'string', 'max:2000'],
        ]);

        $detalles = $datos['detalles'];
        unset($datos['detalles']);

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            if (! $medico) {
                return response()->json(['mensaje' => 'El usuario no tiene perfil de medico'], 404);
            }
            $datos['medico_id'] = $medico->id;
        } else {
            $medico = Medico::find($datos['medico_id']);
        }

        if (! $medico->verificado) {
            return response()->json(['mensaje' => 'El medico no esta verificado'], 403);
        }

        // La cita debe corresponder al paciente y al medico indicados
        $cita = Citas::find($datos['cita_id']);
        if ($cita->paciente_id !== (int) $datos['paciente_id'] || $cita->medico_id !== (int) $datos['medico_id']) {
            return response()->json([
                'mensaje' => 'La cita no corresponde al paciente y medico indicados',
            ], 422);
        }

        if (! in_array($cita->estado, ['confirmada', 'en_curso', 'finalizada'], true)) {
            return response()->json([
                'mensaje' => 'Solo se puede emitir receta de una cita confirmada, en curso o finalizada',
            ], 422);
        }

        $datos['codigo'] = $this->generarCodigo();
        $datos['estado'] = $datos['estado'] ?? 'vigente';
        // La app no genera firma ni PDF: la firma se calcula aqui y el PDF queda pendiente
        $datos['firma_digital'] = $datos['firma_digital']
            ?? hash('sha256', $datos['codigo'] . '|' . $datos['medico_id'] . '|' . $datos['paciente_id'] . '|' . now()->toIso8601String());
        $datos['pdf_url'] = $datos['pdf_url'] ?? '';

        // Receta y medicamentos juntos: si algo falla, no queda nada a medias
        $receta = DB::transaction(function () use ($datos, $detalles) {
            $nueva = Receta::create($datos);
            $nueva->detalles()->createMany($detalles);
            return $nueva;
        });

        BitacoraAuditoria::registrar('crear', 'receta', $receta->id);

        // Aviso al paciente (push + bandeja). Nunca rompe la creacion.
        $pacienteUsuarioId = Paciente::whereKey($receta->paciente_id)->value('usuario_id');
        if ($pacienteUsuarioId) {
            $nombre = trim(($medico->nombres ?? '') . ' ' . ($medico->apellidos ?? ''));
            Notificador::notificar(
                (int) $pacienteUsuarioId,
                'receta',
                'Nueva receta',
                ($nombre !== '' ? $nombre : 'Tu médico') . ' te emitió una receta.',
                ['pantalla' => 'receta', 'receta_id' => $receta->id]
            );
        }

        return response()->json([
            'mensaje' => 'Receta creada correctamente',
            'receta'  => $receta->load(['paciente:id,nombres,apellidos', 'medico:id,nombres,apellidos', 'detalles']),
        ], 201);
    }

    /**
     * GET /recetas/{receta}
     */
    public function show(Request $request, Receta $receta)
    {
        if (! $this->puedeVer($request->user(), $receta)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver esta receta'], 403);
        }

        return response()->json(
            $receta->load([
                'paciente:id,nombres,apellidos',
                'medico:id,nombres,apellidos,jvpm',
                'cita',
                'detalles',
            ])
        );
    }

    /**
     * PUT /recetas/{receta}
     * medico y admin. No se puede cambiar paciente, medico, cita ni codigo.
     */
    public function update(Request $request, Receta $receta)
    {
        if (! $this->puedeVer($request->user(), $receta)) {
            return response()->json(['mensaje' => 'No tienes permiso para modificar esta receta'], 403);
        }

        $datos = $request->validate([
            'estado'        => ['sometimes', Rule::in(self::ESTADOS)],
            'firma_digital' => ['sometimes', 'string', 'max:255'],
            'pdf_url'       => ['sometimes', 'string', 'max:255'],
        ]);

        $receta->update($datos);

        BitacoraAuditoria::registrar('actualizar', 'receta', $receta->id);

        return response()->json([
            'mensaje' => 'Receta actualizada correctamente',
            'receta'  => $receta->fresh(),
        ]);
    }

    /**
     * DELETE /recetas/{receta}  (solo admin, lo controla la ruta)
     */
    public function destroy(Receta $receta)
    {
        $id = $receta->id;

        $receta->delete();

        BitacoraAuditoria::registrar('eliminar', 'receta', $id);

        return response()->json(['mensaje' => 'Receta eliminada correctamente']);
    }

    //METODO / ENDPOINT PARA RECETAS POR PDF
        public function pdf(Request $request, Receta $receta)
    {
        $usuario = $request->user();

        // Solo el paciente dueño, el medico que la emitio, o admin
        if ($usuario->rol !== 'admin') {
            $medicoId = $usuario->rol === 'medico' ? optional($this->medicoDe($usuario))->id : null;
            $pacienteId = $usuario->rol === 'paciente'
                ? Paciente::where('usuario_id', $usuario->id)->value('id')
                : null;

            $permitido = ($medicoId && (int) $medicoId === (int) $receta->medico_id)
                || ($pacienteId && (int) $pacienteId === (int) $receta->paciente_id);

            if (! $permitido) {
                return response()->json(['mensaje' => 'No autorizado'], 403);
            }
        }

        $receta->load(['paciente', 'medico', 'detalles']);

        $fecha = \Carbon\Carbon::parse($receta->created_at ?? $receta->creado_en ?? now())
            ->locale('es')
            ->isoFormat('D [de] MMMM [de] YYYY');

        $pdf = Pdf::loadView('recetas.pdf', ['receta' => $receta, 'fecha' => $fecha])
            ->setPaper('letter');

        return response()->json([
            'nombre' => 'receta-' . $receta->codigo . '.pdf',
            'base64' => base64_encode($pdf->output()),
        ]);
    }
}