<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Idioma;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD web de Idiomas (solo Admin).
 * El controller del API (App\Http\Controllers\IdiomaController) no se toca.
 */
class IdiomaController extends Controller
{
    //------------LISTADO + BUSQUEDA--------------------
    public function index(Request $request)
    {
        $idiomas = $this->consulta($request)->paginate(10)->withQueryString();

        return view('admin.idiomas.index', [
            'idiomas' => $idiomas,
            'q'       => trim((string) $request->query('q', '')),
        ]);
    }

    //------------CREAR--------------------
    public function create()
    {
        return view('admin.idiomas.create');
    }

    public function store(Request $request)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        Idioma::create($datos);

        return redirect()
            ->route('admin.idiomas.index')
            ->with('ok', 'El idioma se registró correctamente.');
    }

    //------------EDITAR--------------------
    public function edit(Idioma $idioma)
    {
        return view('admin.idiomas.edit', ['idioma' => $idioma]);
    }

    public function update(Request $request, Idioma $idioma)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas($idioma), $this->mensajes());

        $idioma->update($datos);

        return redirect()
            ->route('admin.idiomas.index')
            ->with('ok', 'El idioma se actualizó correctamente.');
    }

    //------------ELIMINAR (responde JSON para SweetAlert)--------------------
    public function destroy(Idioma $idioma)
    {
        $pacientes = $idioma->pacientes()->count();
        $medicos   = $idioma->medicos()->count();

        //la FK de medico_idioma es restrict: se avisa antes de que falle
        if ($pacientes > 0 || $medicos > 0) {
            return response()->json([
                'res'     => false,
                'message' => "No se puede eliminar: el idioma está en uso por {$pacientes} paciente(s) y {$medicos} médico(s).",
            ], 409);
        }

        try {
            $idioma->delete();
        } catch (QueryException $e) {
            return response()->json([
                'res'     => false,
                'message' => 'No se pudo eliminar porque el idioma tiene registros relacionados.',
            ], 409);
        }

        return response()->json(['res' => true]);
    }

    //------------REPORTE PDF (respeta la busqueda activa)--------------------
    public function reporte(Request $request)
    {
        $idiomas = $this->consulta($request)->get();
        $usuario = $request->user();

        $pdf = Pdf::loadView('admin.idiomas.reporte', [
            'idiomas'   => $idiomas,
            'q'         => trim((string) $request->query('q', '')),
            'generadoPor' => $usuario->name ?? $usuario->nombre ?? $usuario->email ?? 'Administrador',
            'fecha'     => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('reporte_idiomas_' . now()->format('Ymd_His') . '.pdf');
    }

    //------------PRIVADOS--------------------
    private function consulta(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        return Idioma::query()
            ->withCount(['pacientes', 'medicos'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('codigo', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre');
    }

    private function normalizar(Request $request): void
    {
        $request->merge([
            'nombre' => trim((string) $request->input('nombre')),
            'codigo' => strtolower(trim((string) $request->input('codigo'))),
        ]);
    }

    private function reglas(?Idioma $idioma = null): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:50',
                Rule::unique('idiomas', 'nombre')->ignore($idioma?->id),
            ],
            'codigo' => ['required', 'string', 'max:5', 'alpha_dash'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre del idioma es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 50 caracteres.',
            'nombre.unique'   => 'Ya existe un idioma con ese nombre.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.max'      => 'El código no puede superar los 5 caracteres.',
            'codigo.alpha_dash' => 'El código solo admite letras, números y guiones (ej. es, en, es-GT).',
        ];
    }
}
