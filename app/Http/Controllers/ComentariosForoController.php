<?php

namespace App\Http\Controllers;

use App\Models\ComentariosForo;
use App\Models\PublicacionForo;
use Illuminate\Http\Request;

class ComentariosForoController extends Controller
{
    // Relaciones que se cargan para poder armar el nombre del autor
    private const CON = [
        'autor:id,rol',
        'autor.paciente:id,usuario_id,nombres,apellidos',
        'autor.medico:id,usuario_id,nombres,apellidos',
        'publicacion:id,autor_id,anonimo,oculta',
    ];

    //moderacion: si la publicacion esta oculta, solo el admin puede tocar sus comentarios
    private function publicacionOculta(?PublicacionForo $publicacion, $usuario)
    {
        if ($publicacion && $publicacion->oculta && $usuario->rol !== 'admin') {
            return response()->json(['mensaje' => 'Publicacion no encontrada'], 404);
        }
        return null;
    }

    private function transformar(ComentariosForo $c, $usuarioActual): array
    {
        $autor = $c->autor;
        $pub   = $c->publicacion;

        $ocultar = $pub && $pub->anonimo && $pub->autor_id === $c->autor_id;

        if ($ocultar) {
            $datosAutor = ['id' => null, 'rol' => null, 'nombre' => 'Anonimo'];
        } else {
            $nombre = match ($autor?->rol) {
                'medico'   => $autor->medico
                                ? trim("Dr(a). {$autor->medico->nombres} {$autor->medico->apellidos}")
                                : 'Médico',
                'paciente' => trim(($autor->paciente?->nombres ?? '').' '.($autor->paciente?->apellidos ?? ''))
                                ?: 'Paciente',
                'admin'    => 'Administrador',
                default    => 'Usuario',
            };

            $datosAutor = ['id' => $autor?->id, 'rol' => $autor?->rol, 'nombre' => $nombre];
        }

        return [
            'id'             => $c->id,
            'publicacion_id' => $c->publicacion_id,
            'contenido'      => $c->contenido,
            'creado_en'      => $c->creado_en,
            'actualizado_en' => $c->updated_at,
            'editado'        => $c->updated_at && $c->created_at && $c->updated_at->gt($c->created_at),
            'es_mio'         => $c->autor_id === $usuarioActual->id, //para mostrar editar/borrar en la app
            'autor'          => $datosAutor,
        ];
    }

    // ------------------------------------------------------------------
    // GET /foro/publicaciones/{publicacion}/comentarios
    // Los tres roles. Orden: del mas antiguo al mas reciente.
    // ?per_page=20
    // ------------------------------------------------------------------
    public function index(Request $request, PublicacionForo $publicacion)
    {
        if ($r = $this->publicacionOculta($publicacion, $request->user())) return $r;

        $request->validate(['per_page' => 'nullable|integer|min:1|max:100']);

        $comentarios = ComentariosForo::with(self::CON)
            ->where('publicacion_id', $publicacion->id)
            ->orderBy('creado_en')
            ->orderBy('id')
            ->paginate($request->integer('per_page', 20));

        $usuario = $request->user();
        $comentarios->through(fn ($c) => $this->transformar($c, $usuario));

        return response()->json($comentarios);
    }

    // ------------------------------------------------------------------
    // POST /foro/publicaciones/{publicacion}/comentarios
    // Los tres roles. El autor siempre es el usuario del token.
    // ------------------------------------------------------------------
    public function store(Request $request, PublicacionForo $publicacion)
    {
        if ($r = $this->publicacionOculta($publicacion, $request->user())) return $r;

        $datos = $request->validate([
            'contenido' => 'required|string|min:1|max:2000',
        ]);

        $comentario = ComentariosForo::create([
            'publicacion_id' => $publicacion->id,
            'autor_id'       => $request->user()->id, //nunca se recibe del body
            'contenido'      => trim($datos['contenido']),
            'creado_en'      => now(),
        ]);

        $comentario->load(self::CON);

        return response()->json([
            'mensaje' => 'Comentario creado correctamente',
            'data'    => $this->transformar($comentario, $request->user()),
        ], 201);
    }

    // ------------------------------------------------------------------
    // GET /foro/comentarios/{comentario}
    // ------------------------------------------------------------------
    public function show(Request $request, ComentariosForo $comentario)
    {
        $comentario->load(self::CON);

        if ($r = $this->publicacionOculta($comentario->publicacion, $request->user())) return $r;

        return response()->json([
            'data' => $this->transformar($comentario, $request->user()),
        ]);
    }

    // ------------------------------------------------------------------
    // PUT /foro/comentarios/{comentario}
    // Solo el autor del comentario (ni el admin puede editar lo que escribio otro).
    // ------------------------------------------------------------------
    public function update(Request $request, ComentariosForo $comentario)
    {
        $comentario->loadMissing('publicacion:id,autor_id,anonimo,oculta');
        if ($r = $this->publicacionOculta($comentario->publicacion, $request->user())) return $r;

        if ($comentario->autor_id !== $request->user()->id) {
            return response()->json(['mensaje' => 'Solo el autor puede editar este comentario'], 403);
        }

        $datos = $request->validate([
            'contenido' => 'required|string|min:1|max:2000',
        ]);

        $comentario->update(['contenido' => trim($datos['contenido'])]);
        $comentario->load(self::CON);

        return response()->json([
            'mensaje' => 'Comentario actualizado correctamente',
            'data'    => $this->transformar($comentario, $request->user()),
        ]);
    }

    // ------------------------------------------------------------------
    // DELETE /foro/comentarios/{comentario}
    // admin
    // ------------------------------------------------------------------
    public function destroy(Request $request, ComentariosForo $comentario)
    {
        $usuario = $request->user();

        $comentario->loadMissing('publicacion:id,autor_id,anonimo,oculta');
        if ($r = $this->publicacionOculta($comentario->publicacion, $usuario)) return $r;

        if ($comentario->autor_id !== $usuario->id && $usuario->rol !== 'admin') {
            return response()->json(['mensaje' => 'No puedes eliminar este comentario'], 403);
        }

        $comentario->delete();

        return response()->json(['mensaje' => 'Comentario eliminado correctamente']);
    }
}
