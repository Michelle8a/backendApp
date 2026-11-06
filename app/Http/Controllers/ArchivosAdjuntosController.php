<?php

namespace App\Http\Controllers;

use App\Models\ArchivosAdjuntos;
use App\Models\BitacoraAuditoria;
use App\Models\Paciente;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ArchivosAdjuntosController extends Controller
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

    //admin escribe en cualquiera, el paciente solo en los suyos, el medico no escribe
    private function puedeEscribir(Usuario $user, Paciente $paciente): bool
    {
        return $user->tieneRol('admin')
            || ($user->tieneRol('paciente') && $paciente->usuario_id === $user->id);
    }

    //la ruta interna del archivo nunca se manda al cliente
    private function formatear(ArchivosAdjuntos $archivo): ArchivosAdjuntos
    {
        return $archivo->makeHidden('url');
    }

    //GET /pacientes/{paciente}/archivos_adjuntos

    public function index(Request $request, Paciente $paciente)
    {
        if (!$this->puedeVer($this->usuarioActual(), $paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $filtros = $request->validate([
            'tipo'=>'sometimes|in:imagen,video,pdf',
            'historial_id'=>'sometimes|integer',
            'sesion_id'=>'sometimes|integer',
        ]);

        $consulta = $paciente->archivosAdjuntos()->orderByDesc('subido_en');

        foreach (['tipo', 'historial_id', 'sesion_id'] as $campo) {
            if (isset($filtros[$campo])) {
                $consulta->where($campo, $filtros[$campo]);
            }
        }

        $archivos = $consulta->get()->each->makeHidden('url');

        return response()->json($archivos, 200);
    }

    //POST /pacientes/{paciente}/archivos_adjuntos
    public function store(Request $request, Paciente $paciente)
    {
        $user = $this->usuarioActual();

        if (!$this->puedeEscribir($user, $paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $datos = $request->validate([
            'archivo'=> 'required|file|mimes:jpg,jpeg,png,mp4,pdf|max:20480', //20 MB
            //el historial y la sesion deben pertenecer a ESTE paciente
            'historial_id' => [
                'nullable',
                Rule::exists('historial_medico', 'id')->where('paciente_id', $paciente->id),
            ],
            'sesion_id' => [
                'nullable',
                Rule::exists('sesiones_admision', 'id')->where('paciente_id', $paciente->id),
            ],
        ]);

        $archivo = $request->file('archivo');

        //el tipo se deduce del archivo, no del cliente
        $mime = $archivo->getMimeType();
        $tipo = str_starts_with($mime, 'image/') ? 'imagen'
              : (str_starts_with($mime, 'video/') ? 'video' : 'pdf');

        //disco privado: storage/app/private/archivos_adjuntos/{paciente_id}/...
        $ruta = $archivo->store("archivos_adjuntos/{$paciente->id}", 'local');

        $registro = ArchivosAdjuntos::create([
            'paciente_id' => $paciente->id, //sale de la ruta, nunca del body
            'historial_id'=> $datos['historial_id'] ?? null,
            'sesion_id'=> $datos['sesion_id'] ?? null,
            'url' => $ruta,
            'tipo' => $tipo,
            'subido_en'=> now(),
        ]);

        BitacoraAuditoria::registrar('crear', 'archivo_adjunto', $registro->id);

        return response()->json($this->formatear($registro), 201);
    }

    //GET /archivos_adjuntos/{archivo}  (metadatos)
    public function show(ArchivosAdjuntos $archivo)
    {
        if (!$this->puedeVer($this->usuarioActual(), $archivo->paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return response()->json($this->formatear($archivo), 200);
    }

    //GET /archivos_adjuntos/{archivo}/descargar  (el archivo en si)
    public function descargar(ArchivosAdjuntos $archivo)
    {
        if (!$this->puedeVer($this->usuarioActual(), $archivo->paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        if (!Storage::disk('local')->exists($archivo->url)) {
            return response()->json(['message' => 'El archivo no existe en el servidor'], 404);
        }

        BitacoraAuditoria::registrar('descargar', 'archivo_adjunto', $archivo->id);

        return Storage::disk('local')->download($archivo->url);
    }

    //DELETE /archivos_adjuntos/{archivo}
    public function destroy(ArchivosAdjuntos $archivo)
    {
        if (!$this->puedeEscribir($this->usuarioActual(), $archivo->paciente)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        Storage::disk('local')->delete($archivo->url);
        $archivo->delete();

        BitacoraAuditoria::registrar('eliminar', 'archivo_adjunto', $archivo->id);

        return response()->json(['message' => 'Archivo eliminado'], 200);
    }
}