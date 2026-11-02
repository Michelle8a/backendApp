<?php

namespace App\Http\Controllers;

use App\Models\HistorialMedico;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\BitacoraAuditoria;

class HistorialMedicoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, HistorialMedico $historialMedico)
    {
        BitacoraAuditoria::registrar('crear', 'historial_medico', $historialMedico->id);
    }

    /**
     * Display the specified resource.
     */
    public function show(HistorialMedico $historialMedico)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HistorialMedico $historialMedico)
    {
        //
        BitacoraAuditoria::registrar('actualizar', 'historial_medico', $historialMedico->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HistorialMedico $historialMedico)
    {
        //
        BitacoraAuditoria::registrar('eliminar', 'historial_medico', $historialMedico->id);
    }
}
