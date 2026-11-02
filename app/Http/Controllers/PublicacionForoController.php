<?php

namespace App\Http\Controllers;

use App\Models\PublicacionForo;
use Illuminate\Http\Request;
use App\Models\Usuario;

class PublicacionForoController extends Controller
{
    //-------- helper: arma la respuesta y respeta el anonimato --------
    private function formatear(PublicacionForo $p, Usuario $user): array
    {
        $esDueno = $p->autor_id === $user->id;
        $puedeVerAutor = !$p->anonimo || $esDueno || $user->tieneRol('admin');

        return [
            'id'          => $p->id,
            'titulo'      => $p->titulo,
            'contenido'   => $p->contenido,
            'anonimo'     => $p->anonimo,
            'creado_en'   => $p->creado_en,
            'categoria'   => $p->categoria_foro?->only(['id', 'nombre']),
            'autor'       => $puedeVerAutor && $p->usuario
                                ? $p->usuario->only(['id', 'rol'])
                                : null,
            'es_mia'      => $esDueno,
            'comentarios_count' => $p->comentarios_count ?? null,
            'me_gusta_count'    => $p->me_gusta_count ?? null,
            'le_gusta'          => (bool) ($p->le_gusta ?? false),
        ];
    }

    private function usuarioActual(): Usuario
    {
        /** @var Usuario $user */
        $user = auth('api')->user();
        return $user;
    }

    private function consulta(Usuario $user)
    {
        return PublicacionForo::with(['usuario:id,rol', 'categoria_foro:id,nombre'])
            ->withCount(['comentarios', 'me_gusta'])
            ->withExists(['me_gusta as le_gusta' => fn ($q) => $q->where('usuarios.id', $user->id)]);
    }

    //-------- GET /foro/publicaciones --------
    public function index(Request $request)
    {
        $user = $this->usuarioActual();

        $query = $this->consulta($user)->orderByDesc('creado_en');

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }
        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(fn ($q) => $q->where('titulo', 'like', "%$b%")
                                       ->orWhere('contenido', 'like', "%$b%"));
        }
        if ($request->boolean('mias')) {
            $query->where('autor_id', $user->id);
        }

        $pagina = $query->paginate($request->integer('por_pagina', 10));
        $pagina->getCollection()->transform(fn ($p) => $this->formatear($p, $user));

        return response()->json($pagina);
    }

    //-------- GET /foro/publicaciones/{publicacion} --------
    public function show(PublicacionForo $publicacion)
    {
        $user = $this->usuarioActual();
        $p = $this->consulta($user)->findOrFail($publicacion->id);

        return response()->json($this->formatear($p, $user));
    }

    //-------- POST /foro/publicaciones --------
    public function store(Request $request)
    {
        $user = $this->usuarioActual();

        $datos = $request->validate([
            'categoria_id' => 'required|integer|exists:categorias_foro,id',
            'titulo'       => 'required|string|max:200',
            'contenido'    => 'required|string',
            'anonimo'      => 'sometimes|boolean',
        ]);

        // el autor SIEMPRE sale del token, nunca del body
        $datos['autor_id'] = $user->id;

        $p = PublicacionForo::create($datos);
        $p = $this->consulta($user)->findOrFail($p->id);

        return response()->json($this->formatear($p, $user), 201);
    }

    //-------- PUT /foro/publicaciones/{publicacion} --------
    public function update(Request $request, PublicacionForo $publicacion)
    {
        $user = $this->usuarioActual();

        if ($publicacion->autor_id !== $user->id && !$user->tieneRol('admin')) {
            return response()->json(['message' => 'No puedes editar esta publicación'], 403);
        }

        $datos = $request->validate([
            'categoria_id' => 'sometimes|integer|exists:categorias_foro,id',
            'titulo'       => 'sometimes|string|max:200',
            'contenido'    => 'sometimes|string',
            'anonimo'      => 'sometimes|boolean',
        ]);

        $publicacion->update($datos);
        $p = $this->consulta($user)->findOrFail($publicacion->id);

        return response()->json($this->formatear($p, $user));
    }

    //-------- DELETE /foro/publicaciones/{publicacion} --------
    public function destroy(PublicacionForo $publicacion)
    {
        $user = $this->usuarioActual();

        if ($publicacion->autor_id !== $user->id && !$user->tieneRol('admin')) {
            return response()->json(['message' => 'No puedes eliminar esta publicación'], 403);
        }

        $publicacion->me_gusta()->detach();
        $publicacion->comentarios()->delete(); // quita esta línea si tu FK de comentarios ya es cascade

        $publicacion->delete();

        return response()->json(['message' => 'Publicación eliminada']);
    }

    //-------- POST /foro/publicaciones/{publicacion}/me-gusta (toggle) --------
    public function toggleMeGusta(PublicacionForo $publicacion)
    {
        $user = auth('api')->user();

        $resultado = $publicacion->me_gusta()->toggle($user->id);
        $leGusta = count($resultado['attached']) > 0;

        return response()->json([
            'le_gusta'       => $leGusta,
            'me_gusta_count' => $publicacion->me_gusta()->count(),
        ]);
    }
}