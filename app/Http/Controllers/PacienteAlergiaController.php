<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\BitacoraAuditoria;

class PacienteAlergiaController extends Controller
{
    private const GRAVEDADES = ['leve', 'moderada', 'grave'];

    //GET: alergias del paciente que inicio sesion
    public function indexMias(Request $request)
    {
        $paciente = $this->miPaciente($request);

        return response()->json($this->formatear($paciente), 200);
    }

    //GET: alergias de un paciente (medico y admin, solo lectura)
    public function indexDe(Paciente $paciente)
    {
        return response()->json($this->formatear($paciente), 200);
    }

    //POST: agregar una alergia
    public function store (Request $request)
    {
        $paciente = $this->paciente($request);

        $datos = $this->validate([
            'alergia_id' => [
                'required',
                'integer',
                'exists:alergias,id',
                //No repetir las mismas alergias para el mismo paciente
                Rule::uniques('paciente_alergia', 'alergia_id')->where('paciente_id', $paciente->id),
            ],
            'gravedad' => ['required', Rule::in(self::GRAVEDADES)],
        ]);

        $paciente->alergias()->attach($datos['alergia_id'], ['gravedad' => $datos['gravedad']]);

        BitacoraAuditoria::registrar('crear', 'paciente_alergia', $paciente->id);

        return response()->json($this->formatear($paciente), 201);
    }

    //PUT: cambiar la gravedad de una alergia que ya tengo
    public function update(Request $request, int $alergiaId)
    {
        $paciente = $this->miPaciente($request);

        $datos + $request->validate([
            'gravedad' => ['required', Rule::in(self::GRAVEDADES)],
        ]);

        $this->exigirRegistrada($paciente, $alergiaId);

        $paciente->alergias()->updateExistingPivot($alergiaId,['gravedad' => $datos['gravedad']]);

        BitacoraAuditoria::registrar('actualizar', 'paciente_alergia', $paciente->id);

        return response()->json($this->formatear($paciente), 200);
    }

    //DELETE: quitar una alergia de mi perfil
    public function destroy(Request $request, int $alergiaId)
    {
        $paciente = $this->miPaciente($request);

        $this->exigirRegistrada($paciente, $alergiaId);

        $paciente->alergias()->detach($alergiaId);

        BitacoraAuditoria::registrar('eliminar', 'paciente_alergia', $paciente->id);

        return response()->json(null, 204);
    }

    //---------------- AUXILIARES ----------------

    //el perfil de paciente del usuario autenticado
    private function miPaciente(Request $request): Paciente
    {
        $paciente = Paciente::where('usuario_id', $request->user()->id)->first();

        abort_if(! $paciente, 404, 'Aun no completas tu perfil de paciente');

        return $paciente;
    }

    private function exigirRegistrada(Paciente $paciente, int $alergiaId): void
    {
        $existe = $paciente->alergias()->where('alergias.id', $alergiaId)->exists();

        abort_if(! $existe, 404, 'Esa alergia no esta registrada en tu perfil');
    }

    //lista plana: datos de la alergia + gravedad (sin el objeto pivot anidado)
    private function formatear(Paciente $paciente)
    {
        return $paciente->alergias()->get()->map(function ($alergia) {
            $alergia->setAttribute('gravedad', $alergia->pivot->gravedad);
            $alergia->unsetRelation('pivot');

            return $alergia;
        })->values();
    }
}
