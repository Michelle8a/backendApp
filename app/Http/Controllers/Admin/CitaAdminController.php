<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Citas;
use App\Models\Medico;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Citas: SOLO REPORTE. El admin consulta y descarga; no crea ni modifica citas desde el panel.
 * Por privacidad no se muestran el motivo de consulta, las notas del medico ni la sala de video.
 */
class CitaAdminController extends Controller
{
    private const ESTADOS = [
        'pendiente'  => 'Pendiente',
        'confirmada' => 'Confirmada',
        'en_curso'   => 'En curso',
        'cancelada'  => 'Cancelada',
        'finalizada' => 'Finalizada',
    ];

    private const TIPOS = ['general' => 'General', 'especialista' => 'Especialista'];

    private const LIMITE_PDF = 2000;

    //GET /admin/citas -> vista previa del reporte con filtros
    public function index(Request $request)
    {
        $f = $this->filtros($request);

        return view('admin.citas.index', [
            'citas'   => $this->consulta($f)->paginate(15)->withQueryString(),
            'resumen' => $this->resumen($f),
            'medicos' => Medico::orderBy('apellidos')->orderBy('nombres')->get(['id', 'nombres', 'apellidos']),
            'estados' => self::ESTADOS,
            'tipos'   => self::TIPOS,
        ]);
    }

    //GET /admin/citas/reporte/pdf -> mismos filtros que la vista
    public function pdf(Request $request)
    {
        $f = $this->filtros($request);

        $total = $this->base($f)->count();
        $citas = $this->consulta($f)->limit(self::LIMITE_PDF)->get();

        $aplicados = array_filter([
            'Paciente' => $f['q'] ?? null,
            'Médico'   => ! empty($f['medico_id'])
                            ? optional(Medico::find($f['medico_id']), fn ($m) => $m->nombres . ' ' . $m->apellidos)
                            : null,
            'Estado'   => self::ESTADOS[$f['estado'] ?? ''] ?? null,
            'Tipo'     => self::TIPOS[$f['tipo'] ?? ''] ?? null,
            'Desde'    => ! empty($f['desde']) ? date('d/m/Y', strtotime($f['desde'])) : null,
            'Hasta'    => ! empty($f['hasta']) ? date('d/m/Y', strtotime($f['hasta'])) : null,
        ]);

        $pdf = Pdf::loadView('admin.reportes.citas', [
            'citas'    => $citas,
            'total'    => $total,
            'limite'   => self::LIMITE_PDF,
            'resumen'  => $this->resumen($f),
            'estados'  => self::ESTADOS,
            'tipos'    => self::TIPOS,
            'filtros'  => $aplicados,
            'generado' => now()->format('d/m/Y H:i'),
            'admin'    => $request->user()->correo ?? 'Administrador',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte_citas_' . now()->format('Ymd_His') . '.pdf');
    }

    //---------------- HELPER ----------------

    private function filtros(Request $request): array
    {
        return $request->validate([
            'q'         => ['nullable', 'string', 'max:150'],
            'estado'    => ['nullable', Rule::in(array_keys(self::ESTADOS))],
            'tipo'      => ['nullable', Rule::in(array_keys(self::TIPOS))],
            'medico_id' => ['nullable', 'integer', 'exists:medicos,id'],
            'desde'     => ['nullable', 'date'],
            'hasta'     => ['nullable', 'date', 'after_or_equal:desde'],
        ]);
    }

    //solo filtros (sin orden ni relaciones): sirve para contar y agrupar
    private function base(array $f)
    {
        $q = trim((string) ($f['q'] ?? ''));

        return Citas::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('paciente', function ($p) use ($q) {
                    $p->where('nombres', 'like', "%{$q}%")
                      ->orWhere('apellidos', 'like', "%{$q}%")
                      ->orWhereRaw("CONCAT(nombres, ' ', apellidos) like ?", ["%{$q}%"]);
                });
            })
            ->when(! empty($f['estado']), fn ($x) => $x->where('estado', $f['estado']))
            ->when(! empty($f['tipo']), fn ($x) => $x->where('tipo', $f['tipo']))
            ->when(! empty($f['medico_id']), fn ($x) => $x->where('medico_id', $f['medico_id']))
            ->when(! empty($f['desde']), fn ($x) => $x->whereDate('fecha_hora', '>=', $f['desde']))
            ->when(! empty($f['hasta']), fn ($x) => $x->whereDate('fecha_hora', '<=', $f['hasta']));
    }

    private function consulta(array $f)
    {
        return $this->base($f)
            ->with(['paciente:id,nombres,apellidos', 'medico:id,nombres,apellidos'])
            ->orderByDesc('fecha_hora')
            ->orderByDesc('id');
    }

    //cantidad de citas por estado, respetando los filtros
    private function resumen(array $f): array
    {
        $conteo = $this->base($f)
            ->select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $resumen = [];
        foreach (array_keys(self::ESTADOS) as $estado) {
            $resumen[$estado] = (int) ($conteo[$estado] ?? 0);
        }

        return $resumen;
    }
}
