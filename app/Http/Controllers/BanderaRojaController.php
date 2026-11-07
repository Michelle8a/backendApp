<?php

namespace App\Http\Controllers;

use App\Models\BanderaRoja;
use App\Models\Citas;
use App\Models\Medico;
use App\Models\BitacoraAuditoria;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BanderaRojaController extends Controller
{

    private function medicoDe($usuario): ?Medico
    {
        return Medico::where('usuario_id', $usuario->id)->first();
    }

    /**
     * admin: todo | medico: solo banderas de sus citas
     * (el paciente no tiene acceso: lo bloquea la ruta)
     */
    private function puedeVer($usuario, BanderaRoja $bandera): bool
    {
        if ($usuario->rol === 'admin') {
            return true;
        }

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            $bandera->loadMissing('cita');
            return $medico && $bandera->cita && $bandera->cita->medico_id === $medico->id;
        }

        return false;
    }

    private function relaciones(): array
    {
        return [
            'cita.paciente:id,nombres,apellidos',
            'sesion_admision:id,cita_id,nivel_urgencia,resumen_para_medico',
        ];
    }

    //---------ENDPOINTS----------

    /**
     * GET /banderas_rojas?cita_id=1&desde=2026-11-01&hasta=2026-11-30&per_page=15
     * medico: solo las de sus citas | admin: todas
     */
    public function index(Request $request)
    {
        $usuario = $request->user();

        $request->validate([
            'cita_id'  => ['nullable', 'integer'],
            'desde'    => ['nullable', 'date'],
            'hasta'    => ['nullable', 'date', 'after_or_equal:desde'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = BanderaRoja::with($this->relaciones())->orderByDesc('creado_en');

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            if (! $medico) {
                return response()->json(['mensaje' => 'El usuario no tiene perfil de medico'], 404);
            }
            $query->whereIn('cita_id', Citas::where('medico_id', $medico->id)->select('id'));
        }

        if ($request->filled('cita_id')) {
            $query->where('cita_id', $request->cita_id);
        }
        if ($request->filled('desde')) {
            $query->where('creado_en', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->where('creado_en', '<=', $request->hasta . ' 23:59:59');
        }

        return response()->json($query->paginate($request->input('per_page', 15)));
    }

    /**
     * POST /banderas_rojas
     * medico (solo sobre sus citas) y admin. Una bandera por cita.
     */
    public function store(Request $request)
    {
        $usuario = $request->user();

        $datos = $request->validate([
            'cita_id' => [
                'required',
                'integer',
                'exists:citas,id',
                Rule::unique('banderas_rojas', 'cita_id'),
            ],
        ]);

        if ($usuario->rol === 'medico') {
            $medico = $this->medicoDe($usuario);
            $cita = Citas::find($datos['cita_id']);

            if (! $medico || $cita->medico_id !== $medico->id) {
                return response()->json([
                    'mensaje' => 'No puedes marcar una bandera roja en una cita que no es tuya',
                ], 403);
            }
        }

        $bandera = BanderaRoja::create([
            'cita_id'   => $datos['cita_id'],
            'creado_en' => now(),
        ]);

        BitacoraAuditoria::registrar('crear', 'bandera_roja', $bandera->id);

        return response()->json([
            'mensaje' => 'Bandera roja registrada correctamente',
            'bandera' => $bandera->load($this->relaciones()),
        ], 201);
    }

    /**
     * GET /banderas_rojas/{bandera}
     */
    public function show(Request $request, BanderaRoja $bandera)
    {
        if (! $this->puedeVer($request->user(), $bandera)) {
            return response()->json(['mensaje' => 'No tienes permiso para ver esta bandera roja'], 403);
        }

        return response()->json($bandera->load($this->relaciones()));
    }

    /**
     * DELETE /banderas_rojas/{bandera}  (solo admin, lo controla la ruta)
     */
    public function destroy(BanderaRoja $bandera)
    {
        $id = $bandera->id;

        $bandera->delete();

        BitacoraAuditoria::registrar('eliminar', 'bandera_roja', $id);

        return response()->json(['mensaje' => 'Bandera roja eliminada correctamente']);
    }
}