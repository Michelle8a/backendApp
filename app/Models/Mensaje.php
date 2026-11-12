<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mensaje extends Model
{
    protected $table = 'mensajes';

    protected $fillable = [
        'conversacion_id',
        'emisor_id',
        'tipo',
        'contenido_cifrado',
        'adjunto_url',
        'leido',
    ];

    protected $casts = [
        'enviado_en' => 'datetime'
    ];

    public $timestamps = false;

    //-------RELACIONES-----------

    public function conversacion(){
        return $this->belongsTo(Conversacion::class, 'conversacion_id');
    }

    public function emisor(){
        return $this->belongsTo(Usuario::class, 'emisor_id');
    }
}
