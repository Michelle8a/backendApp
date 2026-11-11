<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Enfermedad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD web de Enfermedades (solo Admin).
 * El controller del API (App\Http\Controllers\EnfermedadController) no se toca.
 */
class EnfermedadController extends Controller
{
    //------------LISTADO + BUSQUEDA--------------------
    public function index(Request $request)
    {
        $enfermedades = $this->consulta($request)->paginate(10)->withQueryString();

        return view('admin.enfermedades.index', [
            'enfermedades' => $enfermedades,
            'q'            => trim((string) $request->query('q', '')),
        ]);
    }

    //------------CREAR--------------------
    public function create()
    {
        return view('admin.enfermedades.create');
    }

    public function store(Request $request)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        Enfermedad::create($datos);

        return redirect()
            ->route('admin.enfermedades.index')
            ->with('ok', 'La enfermedad se registró correctamente.');
    }

    //------------EDITAR--------------------
    public function edit(Enfermedad $enfermedad)
    {
        return view('admin.enfermedades.edit', ['enfermedad' => $enfermedad]);
    }

    public function update(Request $request, Enfermedad $enfermedad)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas($enfermedad), $this->mensajes());

        $enfermedad->update($datos);

        return redirect()
            ->route('admin.enfermedades.index')
            ->with('ok', 'La enfermedad se actualizó correctamente.');
    }

    //------------ELIMINAR (responde JSON para SweetAlert)--------------------
    public function destroy(Enfermedad $enfermedad)
    {
        $pacientes = $enfermedad->pacientes()->count();

        //la FK de paciente_enfermedad es restrict: se avisa antes de que falle
        if ($pacientes > 0) {
            return response()->json([
                'res'     => false,
                'message' => "No se puede eliminar: la enfermedad está registrada en {$pacientes} paciente(s).",
            ], 409);
        }

        try {
            $enfermedad->delete();
        } catch (QueryException $e) {
            return response()->json([
                'res'     => false,
                'message' => 'No se pudo eliminar porque la enfermedad tiene registros relacionados.',
            ], 409);
        }

        return response()->json(['res' => true]);
    }

    //------------REPORTE PDF (respeta la busqueda activa)--------------------
    public function reporte(Request $request)
    {
        $enfermedades = $this->consulta($request)->get();
        $usuario      = $request->user();

        $pdf = Pdf::loadView('admin.enfermedades.reporte', [
            'enfermedades' => $enfermedades,
            'q'            => trim((string) $request->query('q', '')),
            'generadoPor'  => $usuario->name ?? $usuario->nombre ?? $usuario->email ?? 'Administrador',
            'fecha'        => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('reporte_enfermedades_' . now()->format('Ymd_His') . '.pdf');
    }

    //------------PRIVADOS--------------------
    private function consulta(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        //pacientes_count = total; cronicas_count sale de la columna es_cronica del pivote
        return Enfermedad::query()
            ->withCount([
                'pacientes',
                'pacientes as cronicas_count' => fn ($w) => $w->where('paciente_enfermedad.es_cronica', true),
            ])
            ->when($q !== '', fn ($query) => $query->where('nombre', 'like', "%{$q}%"))
            ->orderBy('nombre');
    }

    private function normalizar(Request $request): void
    {
        $request->merge([
            'nombre' => trim((string) $request->input('nombre')),
        ]);
    }

    private function reglas(?Enfermedad $enfermedad = null): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:120',
                Rule::unique('enfermedades', 'nombre')->ignore($enfermedad?->id),
            ],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre de la enfermedad es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 120 caracteres.',
            'nombre.unique'   => 'Ya existe una enfermedad con ese nombre.',
        ];
    }
}
