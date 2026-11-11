<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraAuditoria extends Model
{
    protected $table = 'bitacora_auditoria';

    // La tabla usa su propia columna "fecha"; no lleva created_at / updated_at.
    public $timestamps = false;

    protected $fillable = [
        'usuario_id',
        'accion',
        'recurso',
        'recurso_id',
        'ip',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    //-------------RELACION-----------
    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    //-------------AYUDANTE PARA REGISTRAR-----------
    /**
     * Anota una acción en la bitácora. Se llama desde los controladores:
     *
     *   BitacoraAuditoria::registrar('crear', 'usuario', $usuario->id);
     *
     * Si no se pasa $usuarioId, se toma el usuario de la sesión web (panel admin)
     * o, si no hay, el del token JWT (app móvil).
     * La IP sale de la petición actual, nunca del cliente.
     */
    public static function registrar(
        string $accion,
        string $recurso,
        ?int $recursoId = null,
        ?int $usuarioId = null
    ): self {
        return static::create([
            'usuario_id' => $usuarioId ?? auth('web')->id() ?? auth('api')->id(),
            'accion'     => $accion,
            'recurso'    => $recurso,
            'recurso_id' => $recursoId,
            'ip'         => request()->ip(),
            'fecha'      => now(),
        ]);
    }
}