<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComentariosForo extends Model
{
    protected $table = 'comentarios_foro';

    protected $fillable = [
        'publicacion_id',
        'autor_id',
        'contenido',
        'creado_en'
    ];

    //------------RELACIONES----------

    public function publicacion(){
        return $this->belongsTo(PublicacionForo::class, 'publicacion_id');
    }

    public function autor(){
        return $this->belongsTo(Usuario::class, 'autor_id');
    }
}
