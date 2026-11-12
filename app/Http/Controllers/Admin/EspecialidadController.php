<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Especialidad;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * CRUD web de Especialidades (solo Admin).
 * Los controllers del API (EspecialidadController y MedicoEspecialidadController) no se tocan.
 */
class EspecialidadController extends Controller
{
    //------------LISTADO + BUSQUEDA--------------------
    public function index(Request $request)
    {
        $especialidades = $this->consulta($request)->paginate(10)->withQueryString();

        return view('admin.especialidades.index', [
            'especialidades' => $especialidades,
            'q'              => trim((string) $request->query('q', '')),
        ]);
    }

    //------------CREAR--------------------
    public function create()
    {
        return view('admin.especialidades.create');
    }

    public function store(Request $request)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas(), $this->mensajes());

        Especialidad::create($datos);

        return redirect()
            ->route('admin.especialidades.index')
            ->with('ok', 'La especialidad se registró correctamente.');
    }

    //------------EDITAR--------------------
    public function edit(Especialidad $especialidad)
    {
        return view('admin.especialidades.edit', ['especialidad' => $especialidad]);
    }

    public function update(Request $request, Especialidad $especialidad)
    {
        $this->normalizar($request);
        $datos = $request->validate($this->reglas($especialidad), $this->mensajes());

        $especialidad->update($datos);

        return redirect()
            ->route('admin.especialidades.index')
            ->with('ok', 'La especialidad se actualizó correctamente.');
    }

    //------------ELIMINAR (responde JSON para SweetAlert)--------------------
    public function destroy(Especialidad $especialidad)
    {
        $medicos = $especialidad->medicos()->count();

        //la FK de medico_especialidad es restrict: se avisa antes de que falle
        if ($medicos > 0) {
            return response()->json([
                'res'     => false,
                'message' => "No se puede eliminar: la especialidad está asignada a {$medicos} médico(s).",
            ], 409);
        }

        try {
            $especialidad->delete();
        } catch (QueryException $e) {
            return response()->json([
                'res'     => false,
                'message' => 'No se pudo eliminar porque la especialidad tiene registros relacionados.',
            ], 409);
        }

        return response()->json(['res' => true]);
    }

    //------------REPORTE PDF (respeta la busqueda activa)--------------------
    public function reporte(Request $request)
    {
        $especialidades = $this->consulta($request)->get();
        $usuario        = $request->user();

        $pdf = Pdf::loadView('admin.especialidades.reporte', [
            'especialidades' => $especialidades,
            'q'              => trim((string) $request->query('q', '')),
            'generadoPor'    => $usuario->name ?? $usuario->nombre ?? $usuario->email ?? 'Administrador',
            'fecha'          => now()->format('d/m/Y H:i'),
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('reporte_especialidades_' . now()->format('Ymd_His') . '.pdf');
    }

    //------------PRIVADOS--------------------
    private function consulta(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        //medicos_count = total; verificados_count cuenta solo los medicos con verificado = true
        return Especialidad::query()
            ->withCount([
                'medicos',
                'medicos as verificados_count' => fn ($w) => $w->where('medicos.verificado', true),
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombre', 'like', "%{$q}%")
                      ->orWhere('descripcion', 'like', "%{$q}%");
                });
            })
            ->orderBy('nombre');
    }

    private function normalizar(Request $request): void
    {
        $descripcion = trim((string) $request->input('descripcion'));

        $request->merge([
            'nombre'      => trim((string) $request->input('nombre')),
            //la columna es nullable: un campo vacio se guarda como NULL
            'descripcion' => $descripcion === '' ? null : $descripcion,
        ]);
    }

    private function reglas(?Especialidad $especialidad = null): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('especialidades', 'nombre')->ignore($especialidad?->id),
            ],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required'    => 'El nombre de la especialidad es obligatorio.',
            'nombre.max'         => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique'      => 'Ya existe una especialidad con ese nombre.',
            'descripcion.max'    => 'La descripción no puede superar los 255 caracteres.',
        ];
    }
}
