<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComentariosForo;
use App\Models\PublicacionForo;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

//Moderacion de comentarios del foro: listado, reporte PDF y eliminar.
//El ComentariosForoController original sigue siendo la API JSON de la app.
class ComentarioForoWebController extends Controller
{
    //tope de filas en el PDF para no agotar memoria con dompdf
    private const MAX_PDF = 2000;

    private function validarFiltros(Request $request): void
    {
        $request->validate([
            'buscar'         => 'nullable|string|max:100',
            'publicacion_id' => 'nullable|integer|exists:publicaciones_foro,id',
            'rol'            => 'nullable|in:paciente,medico,admin',
            'desde'          => 'nullable|date',
            'hasta'          => 'nullable|date|after_or_equal:desde',
        ], [
            'buscar.max'            => 'La búsqueda no puede superar los 100 caracteres.',
            'publicacion_id.exists' => 'La publicación indicada no existe.',
            'rol.in'                => 'El rol seleccionado no es válido.',
            'desde.date'            => 'La fecha inicial no es válida.',
            'hasta.date'            => 'La fecha final no es válida.',
            'hasta.after_or_equal'  => 'La fecha final no puede ser anterior a la inicial.',
        ]);
    }

    //arma la consulta con los filtros del request (la usan la vista y el PDF)
    private function filtrar(Request $request)
    {
        $query = ComentariosForo::with([
                'autor:id,correo,rol,estado',
                'autor.paciente:id,usuario_id,nombres',
                'autor.medico:id,usuario_id,nombres,apellidos',
                'publicacion:id,titulo,autor_id,categoria_id,anonimo,oculta',
                'publicacion.categoria_foro:id,nombre',
            ])
            ->orderByDesc('creado_en')
            ->orderByDesc('id');

        if ($request->filled('publicacion_id')) {
            $query->where('publicacion_id', $request->publicacion_id);
        }

        if ($request->filled('rol')) {
            $query->whereHas('autor', fn ($u) => $u->where('rol', $request->rol));
        }

        if ($request->filled('buscar')) {
            $b = trim($request->buscar);
            $query->where(function ($q) use ($b) {
                $q->where('contenido', 'like', "%{$b}%")
                  ->orWhereHas('autor', fn ($u) => $u->where('correo', 'like', "%{$b}%"))
                  ->orWhereHas('publicacion', fn ($p) => $p->where('titulo', 'like', "%{$b}%"));
            });
        }

        if ($request->filled('desde')) {
            $query->whereDate('creado_en', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('creado_en', '<=', $request->hasta);
        }

        return $query;
    }

    //agrega a cada comentario el nombre real del autor y si en la app aparece como "Anonimo"
    private function decorar(iterable $comentarios): void
    {
        foreach ($comentarios as $c) {
            $a = $c->autor;

            $c->autor_nombre = match ($a?->rol) {
                'medico'   => $a->medico
                                ? trim("Dr(a). {$a->medico->nombres} {$a->medico->apellidos}")
                                : null,
                'paciente' => $a->paciente->nombres ?? null,
                'admin'    => 'Administrador',
                default    => null,
            };

            //misma regla de la API: autor de una publicacion anonima comentando en ella
            $pub = $c->publicacion;
            $c->anonimo_en_app = (bool) ($pub && $pub->anonimo && $pub->autor_id === $c->autor_id);
        }
    }

    //GET /admin/comentarios-foro
    public function show(Request $request)
    {
        $this->validarFiltros($request);

        $comentarios = $this->filtrar($request)->paginate(15)->withQueryString();
        $this->decorar($comentarios->getCollection());

        $publicacionFiltro = $request->filled('publicacion_id')
            ? PublicacionForo::select('id', 'titulo')->find($request->publicacion_id)
            : null;

        return view('admin.comentario_foro.index', compact('comentarios', 'publicacionFiltro'));
    }

    //GET /admin/comentarios-foro/reporte
    public function pdf(Request $request)
    {
        $this->validarFiltros($request);

        $base = $this->filtrar($request);

        //conteos exactos sobre TODO lo filtrado (aunque el PDF se recorte)
        $resumen = [
            'total'         => (clone $base)->reorder()->count(),
            'autores'       => (clone $base)->reorder()->distinct()->count('autor_id'),
            'publicaciones' => (clone $base)->reorder()->distinct()->count('publicacion_id'),
            'en_ocultas'    => (clone $base)->reorder()
                                    ->whereHas('publicacion', fn ($p) => $p->where('oculta', true))
                                    ->count(),
        ];

        $comentarios = (clone $base)->limit(self::MAX_PDF)->get();
        $this->decorar($comentarios);

        //resumen de filtros aplicados para el encabezado del reporte
        $filtros = [];
        if ($request->filled('buscar')) {
            $filtros['Búsqueda'] = $request->buscar;
        }
        if ($request->filled('publicacion_id')) {
            $titulo = PublicacionForo::find($request->publicacion_id)?->titulo;
            $filtros['Publicación'] = '#' . $request->publicacion_id . ($titulo ? ' ' . $titulo : '');
        }
        if ($request->filled('rol')) {
            $filtros['Rol del autor'] = ucfirst($request->rol);
        }
        if ($request->filled('desde')) {
            $filtros['Desde'] = Carbon::parse($request->desde)->format('d/m/Y');
        }
        if ($request->filled('hasta')) {
            $filtros['Hasta'] = Carbon::parse($request->hasta)->format('d/m/Y');
        }

        $truncado    = $resumen['total'] > self::MAX_PDF;
        $generado    = now()->format('d/m/Y H:i');
        $generadoPor = optional($request->user())->correo;

        $pdf = Pdf::loadView('admin.comentario_foro.reporte', compact(
            'comentarios', 'filtros', 'resumen', 'truncado', 'generado', 'generadoPor'
        ))->setPaper('a4', 'landscape');

        return $pdf->download('reporte_comentarios_foro_' . now()->format('Ymd_His') . '.pdf');
    }

    //DELETE /admin/comentarios-foro/{comentario}  (responde JSON para el SweetAlert)
    public function destroy(ComentariosForo $comentario)
    {
        $comentario->delete();

        return response()->json([
            'res'     => true,
            'message' => 'Comentario eliminado.',
        ]);
    }
}
