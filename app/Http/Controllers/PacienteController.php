<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    //GET todos
    public function index()
    {
        //with trae tambien los datos de su usuario e idioma
        return response()->json(Paciente::with(['usuario', 'idioma'])->get(), 200);
    }

    //POST
    public function store(Request $request)
    {

        $datos = $request->validate([
            'usuario_id' => 'required|exists:usuarios,id|unique:pacientes,usuario_id', //que el usuario exista y no tenga ya un paciente
            'idioma_id' => 'required|exists:idiomas,id', //que el idioma exista
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'dui_cifrado' => 'required|string',
            'fecha_nacimiento' => 'required|date|before:today',
            'sexo' => 'required|in:M,F,otro',
            'tipo_sangre' => 'required|string|max:3',
            'direccion' => 'required|string|max:255',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ]);

        //inserta la fila
        $paciente = Paciente::create($datos);
        $paciente->refresh(); //vuelve a leer la fila

        return response()->json($paciente->load(['usuario', 'idioma']), 201);
    }

    //GET uno
    public function show(Paciente $paciente)
    {
        return response()->json($paciente->load(['usuario', 'idioma']), 200);
    }

    //PUT
    public function update(Request $request, Paciente $paciente)
    {
        //no se incluye usuario_id porque un paciente no cambia de usuario
        $datos = $request->validate([
            'idioma_id' => 'sometimes|exists:idiomas,id',
            'nombres' => 'sometimes|string|max:100',
            'apellidos' => 'sometimes|string|max:100',
            'dui_cifrado' => 'sometimes|string',
            'fecha_nacimiento' => 'sometimes|date|before:today',
            'sexo' => 'sometimes|in:M,F,otro',
            'tipo_sangre' => 'sometimes|string|max:3',
            'direccion' => 'sometimes|string|max:255',
            'latitud' => 'sometimes|numeric|between:-90,90',
            'longitud' => 'sometimes|numeric|between:-180,180',
        ]);

        //modifica la fila
        $paciente->update($datos);

        return response()->json($paciente->load(['usuario', 'idioma']), 200);
    }

    //DELETE
    public function destroy(Paciente $paciente)
    {
        $paciente->delete();

        return response()->json(null, 204);
    }
}