<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Farmacia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

/**
 * CRUD web de Farmacias (solo Admin).
 * Los controllers del API (FarmaciaController y RecetaCompartidaController) no se tocan.
 */
class FarmaciaController extends Controller
{
    //------------LISTADO + BUSQUEDA--------------------
    public function index(Request $request)
    {
        $farmacias = $this->consulta($request)->paginate(10)->withQueryString();

        return view('admin.farmacias.index', [
            'farmacias' => $farmacias,
            'q'         => $this->texto($request),
        ]);
    }

    //------------CREAR--------------------
    public function create()
    {
        return view('admin.farmacias.create');
    }

    public function store(Request $request)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        Farmacia::create($datos);

        return redirect()
            ->route('admin.farmacias.index')
            ->with('ok', 'La farmacia se registró correctamente.');
    }

    //------------EDITAR--------------------
    public function edit(Farmacia $farmacia)
    {
        return view('admin.farmacias.edit', ['farmacia' => $farmacia]);
    }

    public function update(Request $request, Farmacia $farmacia)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $farmacia->update($datos);

        return redirect()
            ->route('admin.farmacias.index')
            ->with('ok', 'La farmacia se actualizó correctamente.');
    }

    //------------ELIMINAR (responde JSON para SweetAlert)--------------------
    public function destroy(Farmacia $farmacia)
    {
        $recetas = $farmacia->receta_compartida()->count();

        //recetas_compartidas.farmacia_id es restrict: se avisa antes de que falle
        if ($recetas > 0) {
            return response()->json([
                'res'     => false,
                'message' => "No se puede eliminar: la farmacia tiene {$recetas} receta(s) compartida(s).",
            ], 409);
        }

        try {
            $farmacia->delete();
        } catch (QueryException $e) {
            return response()->json([
                'res'     => false,
                'message' => 'No se pudo eliminar porque la farmacia tiene registros relacionados.',
            ], 409);
        }

        return response()->json(['res' => true]);
    }

    //------------REPORTE PDF (respeta la busqueda activa)--------------------
    public function reporte(Request $request)
    {
        $farmacias = $this->consulta($request)->get();
        $usuario   = $request->user();

        $pdf = Pdf::loadView('admin.farmacias.reporte', [
            'farmacias'   => $farmacias,
            'q'           => $this->texto($request),
            'generadoPor' => $usuario->name ?? $usuario->nombre ?? $usuario->email ?? 'Administrador',
            'fecha'       => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('reporte_farmacias_' . now()->format('Ymd_His') . '.pdf');
    }

    //------------PRIVADOS--------------------
    private function consulta(Request $request)
    {
        $q = $this->texto($request);

        //recetas_count = total compartidas; vigentes_count = solo las que siguen vigentes
        return Farmacia::query()
            ->withCount([
                'receta_compartida as recetas_count',
                'receta_compartida as vigentes_count' => fn ($w) => $w->where('recetas.estado', 'vigente'),
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('direccion', 'like', "%{$q}%")
                      ->orWhere('telefono', 'like', "%{$q}%")
                      ->orWhere('correo', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre');
    }

    private function texto(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }

    private function normalizar(Request $request): void
    {
        $request->merge([
            'nombre'    => trim((string) $request->input('nombre')),
            'direccion' => trim((string) $request->input('direccion')),
            'telefono'  => trim((string) $request->input('telefono')),
            'correo'    => strtolower(trim((string) $request->input('correo'))),
            //se acepta coma o punto como separador decimal
            'latitud'   => str_replace(',', '.', trim((string) $request->input('latitud'))),
            'longitud'  => str_replace(',', '.', trim((string) $request->input('longitud'))),
        ]);
    }

    private function reglas(): array
    {
        return [
            'nombre'    => ['required', 'string', 'max:150'],
            'direccion' => ['required', 'string', 'max:255'],
            'latitud'   => ['required', 'numeric', 'between:-90,90'],
            'longitud'  => ['required', 'numeric', 'between:-180,180'],
            'telefono'  => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
            'correo'    => ['required', 'email', 'max:150'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required'    => 'El nombre de la farmacia es obligatorio.',
            'nombre.max'         => 'El nombre no puede superar los 150 caracteres.',
            'direccion.required' => 'La dirección es obligatoria.',
            'direccion.max'      => 'La dirección no puede superar los 255 caracteres.',
            'latitud.required'   => 'La latitud es obligatoria.',
            'latitud.numeric'    => 'La latitud debe ser un número (ej. 13.6929).',
            'latitud.between'    => 'La latitud debe estar entre -90 y 90.',
            'longitud.required'  => 'La longitud es obligatoria.',
            'longitud.numeric'   => 'La longitud debe ser un número (ej. -89.2182).',
            'longitud.between'   => 'La longitud debe estar entre -180 y 180.',
            'telefono.required'  => 'El teléfono es obligatorio.',
            'telefono.max'       => 'El teléfono no puede superar los 20 caracteres.',
            'telefono.regex'     => 'El teléfono solo admite números, espacios y los símbolos + - ( ).',
            'correo.required'    => 'El correo es obligatorio.',
            'correo.email'       => 'Escribe un correo válido (ej. contacto@farmacia.com).',
            'correo.max'         => 'El correo no puede superar los 150 caracteres.',
        ];
    }
}
