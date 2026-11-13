<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'usuario_id',
        'cita_id',
        'tipo',
        'titulo',
        'cuerpo',
        'datos',
        'leida',
        'programada_para',
        'enviada_en'
    ];

    protected $casts = [
        'leida'           => 'boolean',
        'datos'           => 'array',
        'programada_para' => 'datetime',
        'enviada_en'      => 'datetime',
    ];


    //----------

    public function usuario(){
        return $this->belongsTo(Usuario::class);
    }
}
