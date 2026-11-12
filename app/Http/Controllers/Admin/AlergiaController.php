<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alergia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD web de Alergias (solo Admin).
 * El controller del API (App\Http\Controllers\AlergiaController) no se toca.
 */
class AlergiaController extends Controller
{
    //------------LISTADO + BUSQUEDA--------------------
    public function index(Request $request)
    {
        $alergias = $this->consulta($request)->paginate(10)->withQueryString();

        return view('admin.alergias.index', [
            'alergias' => $alergias,
            'q'        => trim((string) $request->query('q', '')),
        ]);
    }

    //------------CREAR--------------------
    public function create()
    {
        return view('admin.alergias.create');
    }

    public function store(Request $request)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        Alergia::create($datos);

        return redirect()
            ->route('admin.alergias.index')
            ->with('ok', 'La alergia se registró correctamente.');
    }

    //------------EDITAR--------------------
    public function edit(Alergia $alergia)
    {
        return view('admin.alergias.edit', ['alergia' => $alergia]);
    }

    public function update(Request $request, Alergia $alergia)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas($alergia), $this->mensajes());

        $alergia->update($datos);

        return redirect()
            ->route('admin.alergias.index')
            ->with('ok', 'La alergia se actualizó correctamente.');
    }

    //------------ELIMINAR (responde JSON para SweetAlert)--------------------
    public function destroy(Alergia $alergia)
    {
        $pacientes = $alergia->pacientes()->count();

        //la FK de paciente_alergia es restrict: se avisa antes de que falle
        if ($pacientes > 0) {
            return response()->json([
                'res'     => false,
                'message' => "No se puede eliminar: la alergia está registrada en {$pacientes} paciente(s).",
            ], 409);
        }

        try {
            $alergia->delete();
        } catch (QueryException $e) {
            return response()->json([
                'res'     => false,
                'message' => 'No se pudo eliminar porque la alergia tiene registros relacionados.',
            ], 409);
        }

        return response()->json(['res' => true]);
    }

    //------------REPORTE PDF (respeta la busqueda activa)--------------------
    public function reporte(Request $request)
    {
        $alergias = $this->consulta($request)->get();
        $usuario  = $request->user();

        $pdf = Pdf::loadView('admin.alergias.reporte', [
            'alergias'    => $alergias,
            'q'           => trim((string) $request->query('q', '')),
            'generadoPor' => $usuario->name ?? $usuario->nombre ?? $usuario->email ?? 'Administrador',
            'fecha'       => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('reporte_alergias_' . now()->format('Ymd_His') . '.pdf');
    }

    //------------PRIVADOS--------------------
    private function consulta(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        //pacientes_count = total; leves/moderadas/graves salen de la columna gravedad del pivote
        return Alergia::query()
            ->withCount([
                'pacientes',
                'pacientes as leves_count'     => fn ($w) => $w->where('paciente_alergia.gravedad', 'leve'),
                'pacientes as moderadas_count' => fn ($w) => $w->where('paciente_alergia.gravedad', 'moderada'),
                'pacientes as graves_count'    => fn ($w) => $w->where('paciente_alergia.gravedad', 'grave'),
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

    private function reglas(?Alergia $alergia = null): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('alergias', 'nombre')->ignore($alergia?->id),
            ],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre de la alergia es obligatorio.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique'   => 'Ya existe una alergia con ese nombre.',
        ];
    }
}
