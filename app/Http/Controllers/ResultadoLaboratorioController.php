<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\ResultadoLaboratorio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ResultadoLaboratorioController extends Controller
{
    private const DISCO = 'local'; // privado

    /**
     * Un paciente solo puede tocar lo suyo; medico y admin pueden con cualquiera.
     * Devuelve una respuesta 403 si no tiene acceso, o null si todo bien.
     */
    private function validarAcceso(Request $request, Paciente $paciente)
    {
        $usuario = $request->user();

        if ($usuario->rol === 'paciente' && $paciente->usuario_id !== $usuario->id) {
            return response()->json([
                'mensaje' => 'No tienes permiso para acceder a los resultados de otro paciente',
            ], 403);
        }

        return null;
    }

    // GET /pacientes/{paciente}/resultados_laboratorio
    public function index(Request $request, Paciente $paciente)
    {
        if ($error = $this->validarAcceso($request, $paciente)) {
            return $error;
        }

        $request->validate([
            'tipo_prueba' => 'nullable|string|max:120',
            'desde'       => 'nullable|date',
            'hasta'       => 'nullable|date|after_or_equal:desde',
            'per_page'    => 'nullable|integer|min:1|max:100',
        ]);

        $resultados = $paciente->resultadosLaboratorio()
            ->when($request->tipo_prueba, fn ($q, $v) => $q->where('tipo_prueba', 'like', "%{$v}%"))
            ->when($request->desde, fn ($q, $v) => $q->whereDate('fecha', '>=', $v))
            ->when($request->hasta, fn ($q, $v) => $q->whereDate('fecha', '<=', $v))
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate($request->per_page ?? 15);

        return response()->json($resultados);
    }

    // POST /pacientes/{paciente}/resultados_laboratorio  
    public function store(Request $request, Paciente $paciente)
    {
        if ($error = $this->validarAcceso($request, $paciente)) {
            return $error;
        }

        $datos = $request->validate([
            'tipo_prueba' => 'required|string|max:120',
            'fecha'       => 'required|date|before_or_equal:today',
            'archivo'     => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10 MB
        ]);

        $ruta = $request->file('archivo')
            ->store("resultados_laboratorio/{$paciente->id}", self::DISCO);

        $resultado = ResultadoLaboratorio::create([
            'id_paciente' => $paciente->id,
            'tipo_prueba' => $datos['tipo_prueba'],
            'fecha'       => $datos['fecha'],
            'archivo_url' => $ruta,
        ]);

        return response()->json([
            'mensaje'   => 'Resultado de laboratorio creado correctamente',
            'resultado' => $resultado,
        ], 201);
    }

    // GET /resultados_laboratorio/{resultado}
    public function show(Request $request, ResultadoLaboratorio $resultado)
    {
        if ($error = $this->validarAcceso($request, $resultado->paciente)) {
            return $error;
        }

        return response()->json($resultado);
    }

    // PUT /resultados_laboratorio/{resultado}
    public function update(Request $request, ResultadoLaboratorio $resultado)
    {
        if ($error = $this->validarAcceso($request, $resultado->paciente)) {
            return $error;
        }

        $datos = $request->validate([
            'tipo_prueba' => 'sometimes|required|string|max:120',
            'fecha'       => 'sometimes|required|date|before_or_equal:today',
            'archivo'     => 'sometimes|required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($request->hasFile('archivo')) {
            // borra el archivo anterior y guarda el nuevo
            Storage::disk(self::DISCO)->delete($resultado->archivo_url);

            $datos['archivo_url'] = $request->file('archivo')
                ->store("resultados_laboratorio/{$resultado->id_paciente}", self::DISCO);
        }

        unset($datos['archivo']);
        $resultado->update($datos);

        return response()->json([
            'mensaje'   => 'Resultado de laboratorio actualizado correctamente',
            'resultado' => $resultado->fresh(),
        ]);
    }

    // DELETE /resultados_laboratorio/{resultado}
    public function destroy(Request $request, ResultadoLaboratorio $resultado)
    {
        if ($error = $this->validarAcceso($request, $resultado->paciente)) {
            return $error;
        }

        Storage::disk(self::DISCO)->delete($resultado->archivo_url);
        $resultado->delete();

        return response()->json(['mensaje' => 'Resultado de laboratorio eliminado correctamente']);
    }

    // GET /resultados_laboratorio/{resultado}/archivo
    public function archivo(Request $request, ResultadoLaboratorio $resultado)
    {
        if ($error = $this->validarAcceso($request, $resultado->paciente)) {
            return $error;
        }

        if (! Storage::disk(self::DISCO)->exists($resultado->archivo_url)) {
            return response()->json(['mensaje' => 'El archivo no existe en el servidor'], 404);
        }

        return response()->file(Storage::disk(self::DISCO)->path($resultado->archivo_url));
    }
}