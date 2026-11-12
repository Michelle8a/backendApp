<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CentroSalud;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD web de Centros de Salud (solo Admin).
 * El controller del API (App\Http\Controllers\CentroSaludController) no se toca.
 */
class CentroSaludController extends Controller
{
    //valores del enum de la migracion => etiqueta que se muestra
    private const TIPOS = [
        'hospital'    => 'Hospital',
        'clinica'     => 'Clínica',
        'laboratorio' => 'Laboratorio',
    ];

    //------------LISTADO + BUSQUEDA + FILTRO POR TIPO--------------------
    public function index(Request $request)
    {
        $centros = $this->consulta($request)->paginate(10)->withQueryString();

        return view('admin.centros_salud.index', [
            'centros' => $centros,
            'q'       => $this->texto($request),
            'tipo'    => $this->tipo($request),
            'tipos'   => self::TIPOS,
        ]);
    }

    //------------CREAR--------------------
    public function create()
    {
        return view('admin.centros_salud.create', ['tipos' => self::TIPOS]);
    }

    public function store(Request $request)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        CentroSalud::create($datos);

        return redirect()
            ->route('admin.centros_salud.index')
            ->with('ok', 'El centro de salud se registró correctamente.');
    }

    //------------EDITAR--------------------
    public function edit(CentroSalud $centro)
    {
        return view('admin.centros_salud.edit', [
            'centro' => $centro,
            'tipos'  => self::TIPOS,
        ]);
    }

    public function update(Request $request, CentroSalud $centro)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $centro->update($datos);

        return redirect()
            ->route('admin.centros_salud.index')
            ->with('ok', 'El centro de salud se actualizó correctamente.');
    }

    //------------ELIMINAR (responde JSON para SweetAlert)--------------------
    public function destroy(CentroSalud $centro)
    {
        $medicos = $centro->medicos()->count();

        //medicos.centro_salud_id es restrict: se avisa antes de que falle
        if ($medicos > 0) {
            return response()->json([
                'res'     => false,
                'message' => "No se puede eliminar: el centro de salud tiene {$medicos} médico(s) asignado(s).",
            ], 409);
        }

        try {
            $centro->delete();
        } catch (QueryException $e) {
            return response()->json([
                'res'     => false,
                'message' => 'No se pudo eliminar porque el centro de salud tiene registros relacionados.',
            ], 409);
        }

        return response()->json(['res' => true]);
    }

    //------------REPORTE PDF (respeta busqueda y tipo)--------------------
    public function reporte(Request $request)
    {
        $centros = $this->consulta($request)->get();
        $usuario = $request->user();
        $tipo    = $this->tipo($request);

        $pdf = Pdf::loadView('admin.centros_salud.reporte', [
            'centros'     => $centros,
            'q'           => $this->texto($request),
            'tipoFiltro'  => $tipo !== '' ? self::TIPOS[$tipo] : '',
            'tipos'       => self::TIPOS,
            'generadoPor' => $usuario->name ?? $usuario->nombre ?? $usuario->email ?? 'Administrador',
            'fecha'       => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('reporte_centros_salud_' . now()->format('Ymd_His') . '.pdf');
    }

    //------------PRIVADOS--------------------
    private function consulta(Request $request)
    {
        $q    = $this->texto($request);
        $tipo = $this->tipo($request);

        return CentroSalud::query()
            ->withCount('medicos')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('direccion', 'like', "%{$q}%")
                      ->orWhere('telefono', 'like', "%{$q}%");
                });
            })
            ->when($tipo !== '', fn ($query) => $query->where('tipo', $tipo))
            ->orderBy('nombre');
    }

    private function texto(Request $request): string
    {
        return trim((string) $request->query('q', ''));
    }

    //solo se acepta un tipo valido; cualquier otro valor se ignora
    private function tipo(Request $request): string
    {
        $tipo = (string) $request->query('tipo', '');

        return array_key_exists($tipo, self::TIPOS) ? $tipo : '';
    }

    private function normalizar(Request $request): void
    {
        $request->merge([
            'nombre'    => trim((string) $request->input('nombre')),
            'direccion' => trim((string) $request->input('direccion')),
            'telefono'  => trim((string) $request->input('telefono')),
            //se acepta coma o punto como separador decimal
            'latitud'   => str_replace(',', '.', trim((string) $request->input('latitud'))),
            'longitud'  => str_replace(',', '.', trim((string) $request->input('longitud'))),
        ]);
    }

    private function reglas(): array
    {
        return [
            'nombre'    => ['required', 'string', 'max:150'],
            'tipo'      => ['required', Rule::in(array_keys(self::TIPOS))],
            'direccion' => ['required', 'string', 'max:255'],
            'latitud'   => ['required', 'numeric', 'between:-90,90'],
            'longitud'  => ['required', 'numeric', 'between:-180,180'],
            'telefono'  => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s()]+$/'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required'    => 'El nombre del centro de salud es obligatorio.',
            'nombre.max'         => 'El nombre no puede superar los 150 caracteres.',
            'tipo.required'      => 'Selecciona el tipo de centro.',
            'tipo.in'            => 'El tipo seleccionado no es válido.',
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
        ];
    }
}
