<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitacoraAuditoria;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Bitacora: SOLO LECTURA. No hay crear, editar ni eliminar a proposito:
 * un registro de auditoria que se puede modificar no sirve como auditoria.
 */
class BitacoraAdminController extends Controller
{
    private const LIMITE_PDF = 2000;

    //GET /admin/bitacora -> listar + filtrar
    public function index(Request $request)
    {
        $filtros = $this->filtros($request);

        $registros = $this->consulta($filtros)
            ->paginate(15)
            ->withQueryString();

        return view('admin.bitacora.index', [
            'registros' => $registros,
            'acciones'  => BitacoraAuditoria::select('accion')->distinct()->orderBy('accion')->pluck('accion'),
            'recursos'  => BitacoraAuditoria::select('recurso')->distinct()->orderBy('recurso')->pluck('recurso'),
        ]);
    }

    //GET /admin/bitacora/reporte/pdf -> mismos filtros que el listado
    public function pdf(Request $request)
    {
        $filtros = $this->filtros($request);

        $total = $this->consulta($filtros)->count();
        $registros = $this->consulta($filtros)->limit(self::LIMITE_PDF)->get();

        $aplicados = array_filter([
            'Usuario'  => $filtros['q'] ?? null,
            'Acción'   => $filtros['accion'] ?? null,
            'Recurso'  => $filtros['recurso'] ?? null,
            'Desde'    => isset($filtros['desde']) ? date('d/m/Y', strtotime($filtros['desde'])) : null,
            'Hasta'    => isset($filtros['hasta']) ? date('d/m/Y', strtotime($filtros['hasta'])) : null,
        ]);

        $pdf = Pdf::loadView('admin.reportes.bitacora', [
            'registros' => $registros,
            'total'     => $total,
            'limite'    => self::LIMITE_PDF,
            'filtros'   => $aplicados,
            'generado'  => now()->format('d/m/Y H:i'),
            'admin'     => $request->user()->correo ?? 'Administrador',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte_bitacora_' . now()->format('Ymd_His') . '.pdf');
    }

    //---------------- HELPER ----------------

    private function filtros(Request $request): array
    {
        return $request->validate([
            'q'       => ['nullable', 'string', 'max:150'],
            'accion'  => ['nullable', 'string', 'max:80'],
            'recurso' => ['nullable', 'string', 'max:80'],
            'desde'   => ['nullable', 'date'],
            'hasta'   => ['nullable', 'date', 'after_or_equal:desde'],
        ]);
    }

    private function consulta(array $f)
    {
        $q = trim((string) ($f['q'] ?? ''));

        return BitacoraAuditoria::with('usuario:id,correo,rol')
            ->when($q !== '', fn ($x) => $x->whereHas('usuario', fn ($u) => $u->where('correo', 'like', "%{$q}%")))
            ->when(! empty($f['accion']), fn ($x) => $x->where('accion', $f['accion']))
            ->when(! empty($f['recurso']), fn ($x) => $x->where('recurso', $f['recurso']))
            ->when(! empty($f['desde']), fn ($x) => $x->whereDate('fecha', '>=', $f['desde']))
            ->when(! empty($f['hasta']), fn ($x) => $x->whereDate('fecha', '<=', $f['hasta']))
            ->orderByDesc('fecha')
            ->orderByDesc('id');
    }
}
