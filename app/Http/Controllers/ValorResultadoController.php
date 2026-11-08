<?php

namespace App\Http\Controllers;

use App\Models\ResultadoLaboratorio;
use App\Models\ValorResultado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ValorResultadoController extends Controller
{
    /**
     * Listar los valores de un resultado de laboratorio.
     * GET /resultados_laboratorio/{resultado}/valores
     */
    public function index(Request $request, ResultadoLaboratorio $resultado): JsonResponse
    {
        if (! $this->puedeAcceder($request, $resultado)) {
            return $this->sinPermiso();
        }

        $valores = $resultado->valoresResultado()->orderBy('id')->get();

        return response()->json(['data' => $valores]);
    }

    /**
     * Crear UN valor dentro de un resultado.
     * POST /resultados_laboratorio/{resultado}/valores
     */
    public function store(Request $request, ResultadoLaboratorio $resultado): JsonResponse
    {
        if (! $this->puedeAcceder($request, $resultado)) {
            return $this->sinPermiso();
        }

        $datos = $request->validate([
            'parametro'        => 'required|string|max:100',
            'valor'            => 'required|numeric|between:-99999999.99,99999999.99',
            'unidad'           => 'required|string|max:20',
            'rango_referencia' => 'required|string|max:50',
        ]);

        $valor = $resultado->valoresResultado()->create($datos);

        return response()->json([
            'mensaje' => 'Valor creado correctamente',
            'data'    => $valor,
        ], 201);
    }

    /**
     * Crear VARIOS valores de golpe (un examen suele traer muchos parametros).
     * POST /resultados_laboratorio/{resultado}/valores/lote
     * Es atomico: si uno falla, no se guarda ninguno.
     */
    public function storeLote(Request $request, ResultadoLaboratorio $resultado): JsonResponse
    {
        if (! $this->puedeAcceder($request, $resultado)) {
            return $this->sinPermiso();
        }

        $datos = $request->validate([
            'valores'                      => 'required|array|min:1|max:100',
            'valores.*.parametro'          => 'required|string|max:100',
            'valores.*.valor'              => 'required|numeric|between:-99999999.99,99999999.99',
            'valores.*.unidad'             => 'required|string|max:20',
            'valores.*.rango_referencia'   => 'required|string|max:50',
        ]);

        $creados = DB::transaction(function () use ($resultado, $datos) {
            return collect($datos['valores'])
                ->map(fn ($fila) => $resultado->valoresResultado()->create($fila));
        });

        return response()->json([
            'mensaje' => 'Valores creados correctamente',
            'data'    => $creados,
        ], 201);
    }

    /**
     * Ver un valor.
     * GET /valores_resultado/{valor}
     */
    public function show(Request $request, ValorResultado $valor): JsonResponse
    {
        if (! $this->puedeAcceder($request, $valor->resultadoLaboratorio)) {
            return $this->sinPermiso();
        }

        return response()->json(['data' => $valor]);
    }

    /**
     * Actualizar un valor (parcial o total).
     * PUT /valores_resultado/{valor}
     */
    public function update(Request $request, ValorResultado $valor): JsonResponse
    {
        if (! $this->puedeAcceder($request, $valor->resultadoLaboratorio)) {
            return $this->sinPermiso();
        }

        $datos = $request->validate([
            'parametro'        => 'sometimes|required|string|max:100',
            'valor'            => 'sometimes|required|numeric|between:-99999999.99,99999999.99',
            'unidad'           => 'sometimes|required|string|max:20',
            'rango_referencia' => 'sometimes|required|string|max:50',
        ]);

        $valor->update($datos);

        return response()->json([
            'mensaje' => 'Valor actualizado correctamente',
            'data'    => $valor->fresh(),
        ]);
    }

    /**
     * Eliminar un valor.
     * DELETE /valores_resultado/{valor}
     */
    public function destroy(Request $request, ValorResultado $valor): JsonResponse
    {
        if (! $this->puedeAcceder($request, $valor->resultadoLaboratorio)) {
            return $this->sinPermiso();
        }

        $valor->delete();

        return response()->json(['mensaje' => 'Valor eliminado correctamente']);
    }

    private function puedeAcceder(Request $request, ResultadoLaboratorio $resultado): bool
    {
        $usuario = $request->user();

        if ($usuario->rol !== 'paciente') {
            return true;
        }

        $paciente = $resultado->paciente;

        return $paciente && (int) $paciente->id_usuario === (int) $usuario->id;
    }

    private function sinPermiso(): JsonResponse
    {
        return response()->json([
            'mensaje' => 'No tienes permiso para acceder a este resultado',
        ], 403);
    }
}
