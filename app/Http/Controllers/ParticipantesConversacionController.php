<?php

namespace App\Http\Controllers;

use App\Models\Conversacion;
use App\Models\Usuario;
use App\Models\BitacoraAuditoria;
use Illuminate\Http\Request;

class ParticipantesConversacionController extends Controller
{
    private function esParticipante(Conversacion $conversacion, int $usuarioId): bool
    {
        return $conversacion->participantes()->where('usuarios.id', $usuarioId)->exists();
    }

    /**
     * admin ve datos de contacto; el resto solo lo necesario para mostrar el chat
     */
    private function formatear(Usuario $u, bool $esAdmin): array
    {
        $perfil = $u->rol === 'medico' ? $u->medico : ($u->rol === 'paciente' ? $u->paciente : null);

        $dato = [
            'usuario_id' => $u->id,
            'rol'        => $u->rol,
            'nombres'    => $perfil->nombres ?? null,
            'apellidos'  => $perfil->apellidos ?? null,
            'unido_en'   => $u->pivot->created_at ?? null,
        ];

        if ($esAdmin) {
            $dato['correo']   = $u->correo;
            $dato['telefono'] = $u->telefono;
        }

        return $dato;
    }

    //---------ENDPOINTS----------

    /**
     * GET /conversaciones/{conversacion}/participantes
     * admin y los propios participantes de la conversacion
     */
    public function index(Request $request, Conversacion $conversacion)
    {
        $usuario = $request->user();
        $esAdmin = $usuario->rol === 'admin';

        if (! $esAdmin && ! $this->esParticipante($conversacion, $usuario->id)) {
            return response()->json(['mensaje' => 'No participas en esta conversacion'], 403);
        }

        $participantes = $conversacion->participantes()
            ->with(['paciente:id,usuario_id,nombres,apellidos', 'medico:id,usuario_id,nombres,apellidos'])
            ->get()
            ->map(fn ($u) => $this->formatear($u, $esAdmin));

        return response()->json($participantes);
    }

    /**
     * POST /conversaciones/{conversacion}/participantes
     * solo admin (lo controla la ruta)
     */
    public function store(Request $request, Conversacion $conversacion)
    {
        $datos = $request->validate([
            'usuario_id' => ['required', 'integer', 'exists:usuarios,id'],
        ]);

        if ($this->esParticipante($conversacion, $datos['usuario_id'])) {
            return response()->json([
                'mensaje' => 'El usuario ya es participante de esta conversacion',
            ], 422);
        }

        $conversacion->participantes()->attach($datos['usuario_id']);

        BitacoraAuditoria::registrar('crear', 'participante_conversacion', $conversacion->id);

        return response()->json([
            'mensaje'       => 'Participante agregado correctamente',
            'participantes' => $conversacion->participantes()->pluck('usuarios.id'),
        ], 201);
    }

    /**
     * DELETE /conversaciones/{conversacion}/participantes/{usuario}
     * solo admin (lo controla la ruta)
     */
    public function destroy(Conversacion $conversacion, Usuario $usuario)
    {
        if (! $this->esParticipante($conversacion, $usuario->id)) {
            return response()->json([
                'mensaje' => 'El usuario no es participante de esta conversacion',
            ], 404);
        }

        $conversacion->participantes()->detach($usuario->id);

        BitacoraAuditoria::registrar('eliminar', 'participante_conversacion', $conversacion->id);

        return response()->json(['mensaje' => 'Participante removido correctamente']);
    }
}