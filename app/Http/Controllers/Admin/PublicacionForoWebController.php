<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaForo;
use App\Models\PublicacionForo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

//Moderacion de publicaciones del foro: listado, reporte PDF y ocultar/mostrar.
//El PublicacionForoController original sigue siendo la API JSON de la app.
class PublicacionForoWebController extends Controller
{
    //tope de filas en el PDF para no agotar memoria con dompdf
    private const MAX_PDF = 2000;

    private function validarFiltros(Request $request): void
    {
        $request->validate([
            'buscar'       => 'nullable|string|max:100',
            'categoria_id' => 'nullable|integer|exists:categorias_foro,id',
            'anonimo'      => 'nullable|in:0,1',
            'visibilidad'  => 'nullable|in:visibles,ocultas',
            'desde'        => 'nullable|date',
            'hasta'        => 'nullable|date|after_or_equal:desde',
        ], [
            'buscar.max'            => 'La búsqueda no puede superar los 100 caracteres.',
            'categoria_id.exists'   => 'La categoría seleccionada no existe.',
            'anonimo.in'            => 'El filtro de anonimato no es válido.',
            'visibilidad.in'        => 'El filtro de visibilidad no es válido.',
            'desde.date'            => 'La fecha inicial no es válida.',
            'hasta.date'            => 'La fecha final no es válida.',
            'hasta.after_or_equal'  => 'La fecha final no puede ser anterior a la inicial.',
        ]);
    }

    //arma la consulta con los filtros del request (la usan la vista y el PDF)
    private function filtrar(Request $request)
    {
        $query = PublicacionForo::with([
                'usuario:id,correo,rol,estado',
                'categoria_foro:id,nombre',
                'moderador:id,correo',
            ])
            ->withCount(['comentarios', 'me_gusta'])
            ->orderByDesc('creado_en')
            ->orderByDesc('id');

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('buscar')) {
            $b = trim($request->buscar);
            $query->where(function ($q) use ($b) {
                $q->where('titulo', 'like', "%{$b}%")
                  ->orWhere('contenido', 'like', "%{$b}%")
                  ->orWhereHas('usuario', fn ($u) => $u->where('correo', 'like', "%{$b}%"));
            });
        }

        if ($request->filled('anonimo')) {
            $query->where('anonimo', $request->anonimo === '1');
        }

        if ($request->filled('visibilidad')) {
            $query->where('oculta', $request->visibilidad === 'ocultas');
        }

        if ($request->filled('desde')) {
            $query->whereDate('creado_en', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('creado_en', '<=', $request->hasta);
        }

        return $query;
    }

    //GET /admin/publicaciones-foro
    public function show(Request $request)
    {
        $this->validarFiltros($request);

        $publicaciones = $this->filtrar($request)->paginate(15)->withQueryString();
        $categorias    = CategoriaForo::orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.publicacion_foro.index', compact('publicaciones', 'categorias'));
    }

    //GET /admin/publicaciones-foro/reporte
    public function pdf(Request $request)
    {
        $this->validarFiltros($request);

        $base = $this->filtrar($request);

        //conteos exactos sobre TODO lo filtrado (aunque el PDF se recorte)
        $contar = fn (?callable $extra = null) => (function () use ($base, $extra) {
            $q = (clone $base)->reorder();
            if ($extra) $extra($q);
            return $q->count();
        })();

        $total    = $contar();
        $ocultas  = $contar(fn ($q) => $q->where('oculta', true));
        $anonimas = $contar(fn ($q) => $q->where('anonimo', true));

        $resumen = [
            'total'    => $total,
            'visibles' => $total - $ocultas,
            'ocultas'  => $ocultas,
            'anonimas' => $anonimas,
        ];

        $publicaciones = (clone $base)->limit(self::MAX_PDF)->get();

        //resumen de filtros aplicados para el encabezado del reporte
        $filtros = [];
        if ($request->filled('buscar')) {
            $filtros['Búsqueda'] = $request->buscar;
        }
        if ($request->filled('categoria_id')) {
            $filtros['Categoría'] = CategoriaForo::find($request->categoria_id)?->nombre;
        }
        if ($request->filled('anonimo')) {
            $filtros['Anonimato'] = $request->anonimo === '1' ? 'Solo anónimas' : 'Solo con autor visible';
        }
        if ($request->filled('visibilidad')) {
            $filtros['Visibilidad'] = $request->visibilidad === 'ocultas' ? 'Solo ocultas' : 'Solo visibles';
        }
        if ($request->filled('desde')) {
            $filtros['Desde'] = Carbon::parse($request->desde)->format('d/m/Y');
        }
        if ($request->filled('hasta')) {
            $filtros['Hasta'] = Carbon::parse($request->hasta)->format('d/m/Y');
        }

        $truncado    = $total > self::MAX_PDF;
        $generado    = now()->format('d/m/Y H:i');
        $generadoPor = optional($request->user())->correo;

        $pdf = Pdf::loadView('admin.publicacion_foro.reporte', compact(
            'publicaciones', 'filtros', 'resumen', 'truncado', 'generado', 'generadoPor'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('reporte_publicaciones_foro_' . now()->format('Ymd_His') . '.pdf');
    }

    //PATCH /admin/publicaciones-foro/{publicacion}/visibilidad  (responde JSON para el SweetAlert)
    //body: { "oculta": true|false }
    public function visibilidad(Request $request, PublicacionForo $publicacion)
    {
        $datos = $request->validate([
            'oculta' => 'required|boolean',
        ], [
            'oculta.required' => 'Indica si la publicación debe ocultarse o mostrarse.',
            'oculta.boolean'  => 'El valor de visibilidad no es válido.',
        ]);

        $oculta = (bool) $datos['oculta'];

        //forceFill: estos campos no estan en $fillable a proposito
        $publicacion->forceFill([
            'oculta'     => $oculta,
            'oculta_en'  => $oculta ? now() : null,
            'oculta_por' => $oculta ? $request->user()->id : null,
        ])->save();

        return response()->json([
            'res'     => true,
            'oculta'  => $oculta,
            'message' => $oculta ? 'Publicación oculta.' : 'Publicación visible de nuevo.',
        ]);
    }
}
