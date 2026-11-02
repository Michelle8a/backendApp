<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactoEmergencia;
use App\Models\Paciente;
use App\Models\Usuario;

class ContactoEmergenciaController extends Controller
{
    private function usuarioActual(): Usuario
    {
        /** @var Usuario $user */
        $user = auth('api')->user();
        return $user;
    }

    //admin y medico ven todos; el paciente solo los suyos
    private function puedeVer(Usuario $user, Paciente $paciente): bool
    {
        return $user->tieneRol('admin', 'medico')
            || ($user->tieneRol('paciente') && $paciente->usuario_id === $user->id);
    }

    //admin escribe en cualquiera; el paciente solo en los suyos; el medico no escribe
    private function puedeEscribir(Usuario $user, Paciente $paciente): bool
    {
        return $user->tieneRol('admin')
            || ($user->tieneRol('paciente') && $paciente->usuario_id === $user->id);
    }

    //GET /pacientes/{paciente}/contactos_emergencia
    public function index(Paciente $paciente)
    {
        if (!$this->puedeVer($this->usuarioActual(), $paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json(
            $paciente->contactos_emergencia()->orderBy('nombre')->get(),
            200
        );
    }

    //POST /pacientes/{paciente}/contactos_emergencia
    public function store(Request $request, Paciente $paciente)
    {
        if (!$this->puedeEscribir($this->usuarioActual(), $paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $datos = $request->validate([
            'nombre'     => 'required|string|max:120',
            'telefono'   => 'required|string|max:20',
            'parentesco' => 'required|string|max:50',
        ]);

        //paciente_id se asigna solo desde la relacion, nunca desde el body
        $contacto = $paciente->contactos_emergencia()->create($datos);

        return response()->json($contacto, 201);
    }

    //GET /contactos_emergencia/{contacto}
    public function show(ContactoEmergencia $contacto)
    {
        if (!$this->puedeVer($this->usuarioActual(), $contacto->paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($contacto->load('paciente:id,nombres,apellidos'), 200);
    }

    //PUT /contactos_emergencia/{contacto}
    public function update(Request $request, ContactoEmergencia $contacto)
    {
        if (!$this->puedeEscribir($this->usuarioActual(), $contacto->paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $datos = $request->validate([
            'nombre'     => 'sometimes|string|max:120',
            'telefono'   => 'sometimes|string|max:20',
            'parentesco' => 'sometimes|string|max:50',
        ]);

        $contacto->update($datos);

        return response()->json($contacto, 200);
    }

    //DELETE /contactos_emergencia/{contacto}
    public function destroy(ContactoEmergencia $contacto)
    {
        if (!$this->puedeEscribir($this->usuarioActual(), $contacto->paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $contacto->delete();

        return response()->json(['message' => 'Contacto eliminado'], 200);
    }
}