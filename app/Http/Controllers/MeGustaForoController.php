<?php

namespace App\Http\Controllers;

use App\Models\PublicacionForo;
use Illuminate\Http\Request;

/**
 * Complementa a PublicacionForoController::toggleMeGusta (que se queda igual).
 * La tabla me_gusta_foro es una pivote (PK compuesta publicacion_id + autor_id),
 * por eso se trabaja con la relacion me_gusta() y no con un modelo propio.
 * La PK compuesta tambien evita duplicados a nivel de BD.
 */
class MeGustaForoController extends Controller
{
    // ------------------------------------------------------------------
    // GET /foro/publicaciones/{publicacion}/me-gusta
    // Resumen: cuantos me gusta tiene y si el usuario actual ya dio el suyo.
    // ------------------------------------------------------------------
    public function estado(Request $request, PublicacionForo $publicacion)
    {
        return response()->json([
            'publicacion_id' => $publicacion->id,
            'le_gusta'       => $publicacion->me_gusta()->where('usuarios.id', $request->user()->id)->exists(),
            'me_gusta_count' => $publicacion->me_gusta()->count(),
        ]);
    }

    // ------------------------------------------------------------------
    // PUT /foro/publicaciones/{publicacion}/me-gusta
    // Dar me gusta. Idempotente: si ya lo habia dado, no pasa nada (200).
    // ------------------------------------------------------------------
    public function dar(Request $request, PublicacionForo $publicacion)
    {
        $resultado = $publicacion->me_gusta()->syncWithoutDetaching([$request->user()->id]);
        $esNuevo   = count($resultado['attached']) > 0;

        return response()->json([
            'mensaje'        => $esNuevo ? 'Me gusta registrado' : 'Ya habias dado me gusta',
            'le_gusta'       => true,
            'me_gusta_count' => $publicacion->me_gusta()->count(),
        ], $esNuevo ? 201 : 200);
    }

    // ------------------------------------------------------------------
    // DELETE /foro/publicaciones/{publicacion}/me-gusta
    // Quitar me gusta. Idempotente: si no lo habia dado, no pasa nada (200).
    // ------------------------------------------------------------------
    public function quitar(Request $request, PublicacionForo $publicacion)
    {
        $quitados = $publicacion->me_gusta()->detach($request->user()->id);

        return response()->json([
            'mensaje'        => $quitados > 0 ? 'Me gusta eliminado' : 'No habias dado me gusta',
            'le_gusta'       => false,
            'me_gusta_count' => $publicacion->me_gusta()->count(),
        ]);
    }

    // ------------------------------------------------------------------
    // GET /foro/mis-me-gusta?per_page=10
    // Publicaciones a las que el usuario actual les dio me gusta (las mas recientes primero).
    // No se muestra el autor, asi que no se rompe el anonimato.
    // ------------------------------------------------------------------
    public function mios(Request $request)
    {
        $request->validate(['per_page' => 'nullable|integer|min:1|max:50']);

        $pagina = $request->user()->me_gusta_foro()
            ->with('categoria_foro:id,nombre')
            ->withCount(['comentarios', 'me_gusta'])
            ->orderByPivot('created_at', 'desc')
            ->paginate($request->integer('per_page', 10));

        $pagina->through(fn ($p) => [
            'id'                => $p->id,
            'titulo'            => $p->titulo,
            'anonimo'           => (bool) $p->anonimo,
            'categoria'         => $p->categoria_foro?->only(['id', 'nombre']),
            'comentarios_count' => $p->comentarios_count,
            'me_gusta_count'    => $p->me_gusta_count,
            'le_gusto_en'       => $p->pivot->created_at,
        ]);

        return response()->json($pagina);
    }

    // ------------------------------------------------------------------
    // GET /foro/publicaciones/{publicacion}/me-gusta/usuarios   (SOLO ADMIN)
    // Quien dio me gusta. Solo id y rol (nunca correo ni telefono).
    // En un foro de salud, saber quien reacciona a que puede revelar datos sensibles,
    // por eso los demas roles solo ven el conteo.
    // ------------------------------------------------------------------
    public function usuarios(Request $request, PublicacionForo $publicacion)
    {
        $request->validate(['per_page' => 'nullable|integer|min:1|max:100']);

        $pagina = $publicacion->me_gusta()
            ->orderByPivot('created_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        $pagina->through(fn ($u) => [
            'usuario_id' => $u->id,
            'rol'        => $u->rol,
            'fecha'      => $u->pivot->created_at,
        ]);

        return response()->json($pagina);
    }
}