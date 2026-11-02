<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\CategoriaForo;

class CategoriaForoController extends Controller
{
    //GET /categorias_foro (publica)
    public function index(){
        return response()->json(CategoriaForo::all(), 200);
    }

    //GET /categorias_foro/{categoria} (opcional, ver una)
    public function show(CategoriaForo $categoria){
        return response()->json($categoria, 200);
    }

    //POST /categorias_foro (solo admin)
    public function store(Request $request){
        $datos = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_foro,nombre',
        ]);

        $categoria = CategoriaForo::create($datos);

        return response()->json($categoria, 201);
    }

    //PUT /categorias_foro/{categoria} (solo admin)
    public function update(Request $request, CategoriaForo $categoria){
        $datos = $request->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('categorias_foro', 'nombre')->ignore($categoria->id),
            ],
        ]);

        $categoria->update($datos);

        return response()->json($categoria, 200);
    }

    //DELETE /categorias_foro/{categoria} (solo admin)
    public function destroy(CategoriaForo $categoria){
        if ($categoria->publicaciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar: la categoría tiene publicaciones asociadas'
            ], 409);
        }

        $categoria->delete();

        return response()->json(['message' => 'Categoría eliminada'], 200);
    }
}