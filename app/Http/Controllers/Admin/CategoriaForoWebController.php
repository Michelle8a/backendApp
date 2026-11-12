<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CategoriaForo;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

//Vistas del panel Admin para categorias del foro (el CategoriaForoController original sigue siendo la API JSON)
class CategoriaForoWebController extends Controller
{
    private function mensajes(): array
    {
        return [
            'nombre.required' => 'El nombre de la categoría es obligatorio.',
            'nombre.string'   => 'El nombre debe ser texto.',
            'nombre.max'      => 'El nombre no puede superar los 100 caracteres.',
            'nombre.unique'   => 'Ya existe una categoría con ese nombre.',
        ];
    }

    //GET /admin/categorias-foro
    public function show()
    {
        $categorias = CategoriaForo::withCount('publicaciones')
            ->orderBy('nombre')
            ->get();

        return view('admin.categoria_foro.index', compact('categorias'));
    }

    //GET /admin/categorias-foro/crear
    public function create()
    {
        return view('admin.categoria_foro.create');
    }

    //POST /admin/categorias-foro
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => 'required|string|max:100|unique:categorias_foro,nombre',
        ], $this->mensajes());

        $datos['nombre'] = trim($datos['nombre']);
        CategoriaForo::create($datos);

        return redirect()->route('categoria_foro.show')
            ->with('success', 'La categoría se creó correctamente.');
    }

    //GET /admin/categorias-foro/{categoria}/editar
    public function edit(CategoriaForo $categoria)
    {
        $categoria->loadCount('publicaciones');

        return view('admin.categoria_foro.edit', compact('categoria'));
    }

    //PUT /admin/categorias-foro/{categoria}
    public function update(Request $request, CategoriaForo $categoria)
    {
        $datos = $request->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('categorias_foro', 'nombre')->ignore($categoria->id),
            ],
        ], $this->mensajes());

        $datos['nombre'] = trim($datos['nombre']);
        $categoria->update($datos);

        return redirect()->route('categoria_foro.show')
            ->with('success', 'La categoría se actualizó correctamente.');
    }

    //DELETE /admin/categorias-foro/{categoria}  (responde JSON para el SweetAlert)
    public function destroy(CategoriaForo $categoria)
    {
        if ($categoria->publicaciones()->exists()) {
            return response()->json([
                'res'     => false,
                'message' => 'No se puede eliminar: la categoría tiene publicaciones asociadas.',
            ], 409);
        }

        $categoria->delete();

        return response()->json([
            'res'     => true,
            'message' => 'Categoría eliminada.',
        ]);
    }
}
