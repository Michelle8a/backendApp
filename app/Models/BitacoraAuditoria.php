<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BitacoraAuditoria extends Model
{
    protected $table = 'bitacora_auditoria';

    protected $fillable = [

        'usuario_id',
        'accion',
        'recurso',
        'recurso_id',
        'ip',
        'fecha'
    ];


    //-------------RELACION-----------
    public function usuario(){
        return $this->belongsTo(Usuario::class);
    }
}
