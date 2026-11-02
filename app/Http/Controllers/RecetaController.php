<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\BitacoraAuditoria;

class RecetaController extends Controller
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
    public function store(Request $request, Receta $receta)
    {
        BitacoraAuditoria::registrar('crear', 'receta', $receta->id);
    }

    /**
     * Display the specified resource.
     */
    public function show(Receta $receta)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Receta $receta)
    {
        //
        BitacoraAuditoria::registrar('actualizar', 'receta', $receta->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Receta $receta)
    {
        BitacoraAuditoria::registrar('eliminar', 'receta', $receta->id);
    }
}
