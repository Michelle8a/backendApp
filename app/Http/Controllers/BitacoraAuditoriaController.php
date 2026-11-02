<?php

namespace App\Http\Controllers;

use App\Models\BitacoraAuditoria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * La bitácora es de SOLO LECTURA por la API.
 * Los registros los crea el servidor (BitacoraAuditoria::registrar) cuando
 * ocurre una acción; ningún cliente puede crear, editar ni borrar entradas.
 * Un registro de auditoría que se puede modificar no sirve como auditoría.
 */
class BitacoraAuditoriaController extends Controller
{
    /**
     * GET /bitacora   (solo admin)
     * Filtros que puedne ser:
     *   ?usuario_id=1 &accion=login &recurso=usuario &recurso_id=5
     *   &desde=2026-11-01 &hasta=2026-11-30 &per_page=20
     */
    public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'usuario_id' => ['sometimes', 'integer'],
            'accion'     => ['sometimes', 'string', 'max:80'],
            'recurso'    => ['sometimes', 'string', 'max:80'],
            'recurso_id' => ['sometimes', 'integer'],
            'desde'      => ['sometimes', 'date'],
            'hasta'      => ['sometimes', 'date', 'after_or_equal:desde'],
            'per_page'   => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $consulta = BitacoraAuditoria::with('usuario:id,correo,rol')
            ->orderByDesc('fecha')
            ->orderByDesc('id');

        foreach (['usuario_id', 'accion', 'recurso', 'recurso_id'] as $campo) {
            if (isset($filtros[$campo])) {
                $consulta->where($campo, $filtros[$campo]);
            }
        }

        if (isset($filtros['desde'])) {
            $consulta->whereDate('fecha', '>=', $filtros['desde']);
        }

        if (isset($filtros['hasta'])) {
            $consulta->whereDate('fecha', '<=', $filtros['hasta']);
        }

        return response()->json(
            $consulta->paginate($filtros['per_page'] ?? 20),
            200
        );
    }

    /**
     * GET /bitacora/{bitacora}   (solo admin)
     */
    public function show(BitacoraAuditoria $bitacora): JsonResponse
    {
        return response()->json($bitacora->load('usuario:id,correo,rol'), 200);
    }
}