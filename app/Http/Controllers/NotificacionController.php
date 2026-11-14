<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class NotificacionController extends Controller
{
    /**
     * GET /notificaciones
     * Lista las notificaciones YA ENVIADAS del usuario autenticado.
     * Filtros opcionales: ?leida=0|1  &tipo=mensaje|recordatorio_cita|receta|cita  &per_page=15
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'leida'    => ['sometimes', 'boolean'],
            'tipo'     => ['sometimes', 'in:mensaje,recordatorio_cita,receta,cita'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $consulta = Notificacion::where('usuario_id', $request->user()->id)
            ->whereNotNull('enviada_en')   // las programadas aún no enviadas no se muestran
            ->orderByDesc('enviada_en');

        if (isset($filtros['leida'])) {
            $consulta->where('leida', $request->boolean('leida'));
        }

        if (isset($filtros['tipo'])) {
            $consulta->where('tipo', $filtros['tipo']);
        }

        return response()->json(
            $consulta->paginate($filtros['per_page'] ?? 15),
            200
        );
    }

    /**
     * GET /notificaciones/contador
     * Cantidad de notificaciones sin leer.
     */
    public function contador(Request $request): JsonResponse
    {
        $total = Notificacion::where('usuario_id', $request->user()->id)
            ->whereNotNull('enviada_en')
            ->where('leida', false)
            ->count();

        return response()->json(['no_leidas' => $total], 200);
    }

    /**
     * POST /notificaciones   (solo admin)
     * Crea una notificación para un usuario.
     * Sin programada_para -> se considera enviada ahora.
     * Con programada_para -> queda pendiente (enviada_en = null) hasta que
     * un proceso programado la envíe.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'usuario_id'      => ['required', 'integer', 'exists:usuarios,id'],
            'tipo'            => ['required', 'in:mensaje,recordatorio_cita,receta,cita'],
            'titulo'          => ['required', 'string', 'max:150'],
            'cuerpo'          => ['required', 'string'],
            'programada_para' => ['nullable', 'date', 'after_or_equal:now'],
        ]);

        $datos['enviada_en'] = empty($datos['programada_para']) ? now() : null;

        $notificacion = Notificacion::create($datos);
        $notificacion->refresh(); // trae los valores por defecto (leida = false)

        return response()->json($notificacion, 201);
    }

    /**
     * PATCH /notificaciones/{notificacion}/leer
     * Marca una notificación propia como leída.
     */
    public function marcarLeida(Request $request, Notificacion $notificacion): JsonResponse
    {
        $this->verificarDueno($request, $notificacion);

        $notificacion->update(['leida' => true]);

        return response()->json($notificacion, 200);
    }

    /**
     * PATCH /notificaciones/leer-todas
     * Marca como leídas todas las notificaciones propias.
     */
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $cantidad = Notificacion::where('usuario_id', $request->user()->id)
            ->whereNotNull('enviada_en')
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json(['marcadas' => $cantidad], 200);
    }

    /**
     * DELETE /notificaciones/{notificacion}
     */
    public function destroy(Request $request, Notificacion $notificacion): Response
    {
        $this->verificarDueno($request, $notificacion);

        $notificacion->delete();

        return response()->noContent();
    }

    /**
     * Si la notificación es de otro usuario dice 404,
     * para no revelar que ese id existe.
     */
    private function verificarDueno(Request $request, Notificacion $notificacion): void
    {
        abort_if(
            (int) $notificacion->usuario_id !== (int) $request->user()->id,
            404
        );
    }
}