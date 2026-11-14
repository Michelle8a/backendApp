<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitacoraAuditoria;
use App\Models\CentroSalud;
use App\Models\Especialidad;
use App\Models\Idioma;
use App\Models\Medico;
use App\Models\Usuario;
use App\Services\Notificador;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Vistas web del panel Admin para gestionar médicos.
 * (El MedicoController original sigue siendo la API para la app móvil.)
 */
class MedicoAdminController extends Controller
{
    /* ---------- consulta compartida por el listado y el PDF ---------- */
    private function consultaFiltrada(Request $request)
    {
        $consulta = Medico::with([
            'usuario:id,correo,telefono,estado',
            'centro_salud:id,nombre',
            'especialidades:id,nombre',
            'idiomas:id,nombre',
        ]);

        if ($request->filled('q')) {
            $texto = trim($request->q);
            $consulta->where(function ($w) use ($texto) {
                $w->where('nombres', 'like', "%{$texto}%")
                  ->orWhere('apellidos', 'like', "%{$texto}%")
                  ->orWhere('jvpm', 'like', "%{$texto}%")
                  ->orWhereHas('usuario', fn ($u) => $u->where('correo', 'like', "%{$texto}%"));
            });
        }

        if ($request->filled('centro_salud_id')) {
            $consulta->where('centro_salud_id', $request->centro_salud_id);
        }

        if ($request->filled('especialidad_id')) {
            $consulta->whereHas('especialidades', fn ($e) => $e->where('especialidades.id', $request->especialidad_id));
        }

        if ($request->filled('verificado') && in_array($request->verificado, ['0', '1'], true)) {
            $consulta->where('verificado', $request->verificado);
        }

        if ($request->filled('es_general') && in_array($request->es_general, ['0', '1'], true)) {
            $consulta->where('es_general', $request->es_general);
        }

        return $consulta->orderBy('apellidos')->orderBy('nombres');
    }

    /* ---------- LISTAR + BUSCAR ---------- */
    public function index(Request $request)
    {
        $medicos = $this->consultaFiltrada($request)->paginate(10)->withQueryString();

        return view('admin.medico.index', [
            'medicos'        => $medicos,
            'centros'        => CentroSalud::orderBy('nombre')->get(['id', 'nombre']),
            'especialidades' => Especialidad::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /* ---------- REPORTE PDF (respeta los filtros activos) ---------- */
    public function pdf(Request $request)
    {
        $medicos = $this->consultaFiltrada($request)->get();

        $filtros = [];
        if ($request->filled('q')) $filtros[] = 'Búsqueda: "' . $request->q . '"';
        if ($request->filled('centro_salud_id')) {
            $filtros[] = 'Centro: ' . optional(CentroSalud::find($request->centro_salud_id))->nombre;
        }
        if ($request->filled('especialidad_id')) {
            $filtros[] = 'Especialidad: ' . optional(Especialidad::find($request->especialidad_id))->nombre;
        }
        if ($request->filled('verificado')) {
            $filtros[] = $request->verificado === '1' ? 'Solo verificados' : 'Solo sin verificar';
        }
        if ($request->filled('es_general')) {
            $filtros[] = $request->es_general === '1' ? 'Solo médicos generales' : 'Solo especialistas';
        }

        $pdf = Pdf::loadView('admin.medico.pdf', [
            'medicos'  => $medicos,
            'filtros'  => $filtros,
            'generado' => now()->format('d/m/Y H:i'),
            'admin'    => $request->user()->correo,
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('reporte-medicos-' . now()->format('Ymd-His') . '.pdf');
    }

    /* ---------- VER DETALLE ---------- */
    public function show(Medico $medico)
    {
        $medico->load(['usuario', 'centro_salud', 'especialidades', 'idiomas'])->loadCount('citas');

        return view('admin.medico.show', compact('medico'));
    }

    /* ---------- CREAR ---------- */
    public function create()
    {
        return view('admin.medico.create', $this->catalogos());
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'correo'          => 'required|email|max:120|unique:usuarios,correo',
            'contrasena'      => 'required|string|min:8|max:64|confirmed',
            'telefono'        => 'nullable|string|max:20',
            'centro_salud_id' => 'required|exists:centros_salud,id',
            'jvpm'            => 'required|string|max:100|unique:medicos,jvpm',
            'nombres'         => 'required|string|max:100',
            'apellidos'       => 'required|string|max:100',
            'es_general'      => 'required|boolean',
            'biografia'       => 'nullable|string|max:200',
            'tarifa'          => 'required|numeric|min:0|max:999999.99',
            'verificado'      => 'required|boolean',
            'especialidades'   => 'nullable|array',
            'especialidades.*' => 'integer|exists:especialidades,id',
            'idiomas'          => 'nullable|array',
            'idiomas.*'        => 'integer|exists:idiomas,id',
        ]);

        $medico = DB::transaction(function () use ($datos) {
            $usuario = new Usuario();
            $usuario->forceFill([
                'correo'                  => $datos['correo'],
                'contrasena_hash'         => Hash::make($datos['contrasena']),
                'telefono'                => $datos['telefono'] ?? null,
                'rol'                     => 'medico',
                'estado'                  => 'activo',
                'consentimiento_aceptado' => 1,
                'consentimiento_version'  => '1.0',
                'consentimiento_fecha'    => now(),
            ])->save();

            $medico = new Medico();
            $medico->forceFill([
                'usuario_id'      => $usuario->id,
                'centro_salud_id' => $datos['centro_salud_id'],
                'jvpm'            => $datos['jvpm'],
                'nombres'         => $datos['nombres'],
                'apellidos'       => $datos['apellidos'],
                'es_general'      => $datos['es_general'],
                'biografia'       => $datos['biografia'] ?? null,
                'tarifa'          => $datos['tarifa'],
                'verificado'      => $datos['verificado'],
            ])->save();

            $medico->especialidades()->sync($datos['especialidades'] ?? []);
            $medico->idiomas()->sync($datos['idiomas'] ?? []);

            return $medico;
        });

        BitacoraAuditoria::registrar('crear', 'medico', $medico->id);

        return redirect()->route('medico.index')->with('ok', 'Médico registrado correctamente.');
    }

    /* ---------- EDITAR ---------- */
    public function edit(Medico $medico)
    {
        $medico->load(['usuario', 'especialidades', 'idiomas']);

        return view('admin.medico.edit', array_merge($this->catalogos(), compact('medico')));
    }

    public function update(Request $request, Medico $medico)
    {
        $datos = $request->validate([
            'telefono'        => 'nullable|string|max:20',
            'centro_salud_id' => 'required|exists:centros_salud,id',
            'jvpm'            => ['required', 'string', 'max:100', Rule::unique('medicos', 'jvpm')->ignore($medico->id)],
            'nombres'         => 'required|string|max:100',
            'apellidos'       => 'required|string|max:100',
            'es_general'      => 'required|boolean',
            'biografia'       => 'nullable|string|max:200',
            'tarifa'          => 'required|numeric|min:0|max:999999.99',
            'verificado'      => 'required|boolean',
            'especialidades'   => 'nullable|array',
            'especialidades.*' => 'integer|exists:especialidades,id',
            'idiomas'          => 'nullable|array',
            'idiomas.*'        => 'integer|exists:idiomas,id',
        ]);

        $estabaVerificado = (bool) $medico->verificado;

        DB::transaction(function () use ($datos, $medico) {
            $telefono       = $datos['telefono'] ?? null;
            $especialidades = $datos['especialidades'] ?? [];
            $idiomas        = $datos['idiomas'] ?? [];
            unset($datos['telefono'], $datos['especialidades'], $datos['idiomas']);

            $medico->forceFill($datos)->save();
            $medico->usuario?->forceFill(['telefono' => $telefono])->save();
            $medico->especialidades()->sync($especialidades);
            $medico->idiomas()->sync($idiomas);
        });

        BitacoraAuditoria::registrar('actualizar', 'medico', $medico->id);

        if (! $estabaVerificado && (bool) $medico->fresh()->verificado) {
            $this->avisarVerificacion($medico);
        }

        return redirect()->route('medico.index')->with('ok', 'Médico actualizado correctamente.');
    }

    /* ---------- VERIFICAR / QUITAR VERIFICACION (AJAX) ---------- */
        public function verificar(Medico $medico)
    {
        $medico->forceFill(['verificado' => ! $medico->verificado])->save();
        BitacoraAuditoria::registrar('actualizar', 'medico', $medico->id);

        // Solo avisa al pasar a verificado, no al quitar la verificación
        if ($medico->verificado) {
            $this->avisarVerificacion($medico);
        }

        return response()->json(['res' => true, 'verificado' => $medico->verificado]);
    }

        /** Avisa al médico (push + bandeja) que ya fue verificado. Nunca rompe la acción del admin. */
    private function avisarVerificacion(Medico $medico): void
    {
        if (! $medico->usuario_id) {
            return;
        }

        Notificador::notificar(
            (int) $medico->usuario_id,
            'verificacion',
            'Cuenta verificada',
            'Tu perfil fue verificado. Ya puedes recibir citas de pacientes.',
            ['pantalla' => 'perfil']
        );
    }

    /* ---------- ELIMINAR (AJAX) ---------- */
    public function destroy(Medico $medico)
    {
        try {
            $id = $medico->id;
            $medico->delete();
            BitacoraAuditoria::registrar('eliminar', 'medico', $id);

            return response()->json(['res' => true]);
        } catch (QueryException $e) {
            return response()->json([
                'res' => false,
                'message' => 'No se puede eliminar: el médico tiene citas, recetas u otros registros asociados. Puedes quitarle la verificación para ocultarlo de la app.',
            ], 409);
        }
    }

    private function catalogos(): array
    {
        return [
            'centros'        => CentroSalud::orderBy('nombre')->get(['id', 'nombre']),
            'especialidades' => Especialidad::orderBy('nombre')->get(['id', 'nombre']),
            'idiomas'        => Idioma::orderBy('nombre')->get(['id', 'nombre']),
        ];
    }
}
