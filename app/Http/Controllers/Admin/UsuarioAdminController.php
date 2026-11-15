<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BitacoraAuditoria;
use App\Models\Usuario;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

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

    //GET /admin/usuarios/create
    public function create()
    {
        return view('admin.usuarios.create', ['roles' => self::ROLES]);
    }

    //POST /admin/usuarios
    public function store(Request $request)
    {
        $request->merge(['correo' => mb_strtolower(trim((string) $request->input('correo')))]);

        $datos = $request->validate([
            'correo'     => ['required', 'email', 'max:150', 'unique:usuarios,correo'],
            'contrasena' => ['required', 'string', 'min:8', 'confirmed'],
            'rol'        => ['required', Rule::in(array_keys(self::ROLES))],
            'telefono'   => ['required', 'string', 'max:20'],
        ]);

        //El consentimiento lo debe aceptar la propia persona, no el admin en su nombre:
        //queda como NO aceptado y la app se lo debe pedir en su primer inicio de sesion.
        $usuario = Usuario::create([
            'correo'                  => $datos['correo'],
            'contrasena_hash'         => Hash::make($datos['contrasena']),
            'rol'                     => $datos['rol'],
            'telefono'                => $datos['telefono'],
            'consentimiento_aceptado' => false,
            'consentimiento_fecha'    => now(),
            'consentimiento_version'  => 'pendiente',
        ]);

        BitacoraAuditoria::registrar('crear', 'usuario', $usuario->id, $request->user()->id);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    //GET /admin/usuarios/{usuario}/edit
    public function edit(Request $request, Usuario $usuario)
    {
        [$bloqueado, $motivo] = $this->rolBloqueado($request, $usuario);

        return view('admin.usuarios.edit', [
            'usuario'       => $usuario,
            'roles'         => self::ROLES,
            'rolBloqueado'  => $bloqueado,
            'motivoBloqueo' => $motivo,
        ]);
    }

    //PUT /admin/usuarios/{usuario}
    public function update(Request $request, Usuario $usuario)
    {
        $request->merge(['correo' => mb_strtolower(trim((string) $request->input('correo')))]);

        [$bloqueado] = $this->rolBloqueado($request, $usuario);

        $reglas = [
            'correo'   => ['required', 'email', 'max:150', Rule::unique('usuarios', 'correo')->ignore($usuario->id)],
            'telefono' => ['required', 'string', 'max:20'],
        ];

        if (! $bloqueado) {
            $reglas['rol'] = ['required', Rule::in(array_keys(self::ROLES))];
        }

        $datos = $request->validate($reglas);

        //si el rol esta bloqueado se conserva el actual, aunque lo manden en la peticion
        if ($bloqueado) {
            $datos['rol'] = $usuario->rol;
        }

        $usuario->update($datos);

        BitacoraAuditoria::registrar('actualizar', 'usuario', $usuario->id, $request->user()->id);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    //DELETE /admin/usuarios/{usuario} (responde JSON para SweetAlert)
    public function destroy(Request $request, Usuario $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return response()->json([
                'res'     => false,
                'message' => 'No puedes eliminar tu propia cuenta.',
            ], 422);
        }

        try {
            $usuario->delete();
        } catch (QueryException $e) {
            //las llaves foraneas son "restrict": perfil de paciente/medico, bitacora, etc.
            if ($e->getCode() === '23000') {
                return response()->json([
                    'res'     => false,
                    'message' => 'No se puede eliminar: el usuario tiene registros asociados (perfil, citas o bitácora). Desactívalo en su lugar.',
                ], 422);
            }
            throw $e;
        }

        BitacoraAuditoria::registrar('eliminar', 'usuario', $usuario->id, $request->user()->id);

        return response()->json(['res' => true, 'message' => 'El usuario se eliminó correctamente.']);
    }

    //PATCH /admin/usuarios/{usuario}/estado -> activar / desactivar (responde JSON)
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

        BitacoraAuditoria::registrar('actualizar', 'usuario', $usuario->id, $request->user()->id);

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

    /**
     * El rol no se puede cambiar si:
     *  - es tu propia cuenta (te quedarias sin acceso al panel), o
     *  - el usuario ya tiene perfil de paciente o medico (el rol y el perfil quedarian desalineados).
     * Devuelve [bloqueado, motivo].
     */
    private function rolBloqueado(Request $request, Usuario $usuario): array
    {
        if ($usuario->id === $request->user()->id) {
            return [true, 'No puedes cambiar el rol de tu propia cuenta.'];
        }

        if ($usuario->paciente()->exists()) {
            return [true, 'Este usuario ya tiene perfil de paciente; su rol no se puede cambiar.'];
        }

        if ($usuario->medico()->exists()) {
            return [true, 'Este usuario ya tiene perfil de médico; su rol no se puede cambiar.'];
        }

        return [false, null];
    }

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
