<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitacoraAuditoria;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class UsuarioAdminController extends Controller
{
    private const ROLES   = ['admin' => 'Administrador', 'medico' => 'Médico', 'paciente' => 'Paciente'];
    private const ESTADOS = ['activo' => 'Activo', 'suspendido' => 'Desactivado'];

    //GET /admin/usuarios -> listar + buscar + filtrar
    public function index(Request $request)
    {
        $usuarios = $this->consulta($request)
            ->paginate(10)
            ->withQueryString();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'roles'    => self::ROLES,
            'estados'  => self::ESTADOS,
        ]);
    }

    //PATCH /admin/usuarios/{usuario}/estado -> activar / desactivar (responde JSON para SweetAlert)
    public function cambiarEstado(Request $request, Usuario $usuario)
    {
        $datos = $request->validate([
            'estado' => 'required|in:activo,suspendido',
        ]);

        //un admin no puede desactivarse a si mismo (se quedaria fuera del panel)
        if ($usuario->id === $request->user()->id && $datos['estado'] === 'suspendido') {
            return response()->json([
                'res'     => false,
                'message' => 'No puedes desactivar tu propia cuenta.',
            ], 422);
        }

        //"estado" no esta en $fillable del modelo, por eso se asigna directo
        $usuario->estado = $datos['estado'];
        $usuario->save();

        BitacoraAuditoria::registrar('actualizar', 'usuario', $usuario->id);

        return response()->json([
            'res'     => true,
            'estado'  => $usuario->estado,
            'message' => $usuario->estado === 'activo'
                            ? 'El usuario fue activado.'
                            : 'El usuario fue desactivado.',
        ]);
    }

    //GET /admin/usuarios/reporte/pdf -> mismos filtros que el listado
    public function pdf(Request $request)
    {
        $usuarios = $this->consulta($request)->get();

        $filtros = array_filter([
            'Búsqueda' => $request->input('q'),
            'Rol'      => self::ROLES[$request->input('rol')] ?? null,
            'Estado'   => self::ESTADOS[$request->input('estado')] ?? null,
        ]);

        $pdf = Pdf::loadView('admin.reportes.usuarios', [
            'usuarios' => $usuarios,
            'filtros'  => $filtros,
            'roles'    => self::ROLES,
            'estados'  => self::ESTADOS,
            'generado' => now()->format('d/m/Y H:i'),
            'admin'    => $request->user()->correo ?? 'Administrador',
        ])->setPaper('a4', 'portrait');

        return $pdf->download('reporte_usuarios_' . now()->format('Ymd_His') . '.pdf');
    }

    //---------------- HELPER ----------------

    private function consulta(Request $request)
    {
        $q = trim((string) $request->input('q'));

        return Usuario::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('correo', 'like', "%{$q}%")
                      ->orWhere('telefono', 'like', "%{$q}%");
                });
            })
            ->when($request->filled('rol'), fn ($x) => $x->where('rol', $request->rol))
            ->when($request->filled('estado'), fn ($x) => $x->where('estado', $request->estado))
            ->orderBy('id');
    }
}
