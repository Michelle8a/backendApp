<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Idioma;
use App\Models\Paciente;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PacienteAdminController extends Controller
{
    private const TIPOS_SANGRE = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    //GET /admin/pacientes  -> listar + buscar + filtrar
    public function index(Request $request)
    {
        $pacientes = $this->consulta($request)
            ->paginate(10)
            ->withQueryString();

        return view('admin.pacientes.index', [
            'pacientes'   => $pacientes,
            'idiomas'     => Idioma::all(),
            'tiposSangre' => self::TIPOS_SANGRE,
        ]);
    }

    //GET /admin/pacientes/{paciente}  -> detalle (solo lectura)
    public function show(Paciente $paciente)
    {
        $paciente->load(['usuario:id,correo,telefono,rol', 'idioma', 'alergias', 'enfermedades'])
                 ->loadCount(['citas', 'contactos_emergencia']);

        //el DUI esta cifrado: en pantalla solo se muestra enmascarado
        try {
            $dui = Crypt::decryptString($paciente->dui_cifrado);
            $duiEnmascarado = '********-' . substr($dui, -1);
        } catch (\Throwable $e) {
            $duiEnmascarado = 'No disponible';
        }

        return view('admin.pacientes.show', compact('paciente', 'duiEnmascarado'));
    }

    //GET /admin/pacientes/reporte/pdf  -> respeta los mismos filtros del listado
    public function pdf(Request $request)
    {
        $pacientes = $this->consulta($request)->get();

        $filtros = array_filter([
            'Búsqueda'       => $request->input('q'),
            'Sexo'           => $request->input('sexo'),
            'Tipo de sangre' => $request->input('tipo_sangre'),
            'Idioma'         => $request->filled('idioma_id')
                                    ? optional(Idioma::find($request->idioma_id))->nombre
                                    : null,
        ]);

        $pdf = Pdf::loadView('admin.reportes.pacientes', [
            'pacientes' => $pacientes,
            'filtros'   => $filtros,
            'generado'  => now()->format('d/m/Y H:i'),
            'admin'     => $request->user()->correo ?? 'Administrador',
        ])->setPaper('a4', 'landscape');

        return $pdf->download('reporte_pacientes_' . now()->format('Ymd_His') . '.pdf');
    }

    //---------------- HELPER ----------------

    //misma consulta para listado y reporte, asi lo que se ve es lo que se imprime
    private function consulta(Request $request)
    {
        $q = trim((string) $request->input('q'));

        return Paciente::with(['usuario:id,correo,telefono,rol', 'idioma'])
            ->withCount('citas')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nombres', 'like', "%{$q}%")
                      ->orWhere('apellidos', 'like', "%{$q}%")
                      ->orWhereRaw("CONCAT(nombres, ' ', apellidos) like ?", ["%{$q}%"])
                      ->orWhereHas('usuario', function ($u) use ($q) {
                          $u->where('correo', 'like', "%{$q}%")
                            ->orWhere('telefono', 'like', "%{$q}%");
                      });
                });
            })
            ->when($request->filled('sexo'), fn ($x) => $x->where('sexo', $request->sexo))
            ->when($request->filled('tipo_sangre'), fn ($x) => $x->where('tipo_sangre', $request->tipo_sangre))
            ->when($request->filled('idioma_id'), fn ($x) => $x->where('idioma_id', $request->idioma_id))
            ->orderBy('apellidos')
            ->orderBy('nombres');
    }
}
