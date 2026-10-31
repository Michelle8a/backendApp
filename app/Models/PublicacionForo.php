<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicacionForo extends Model
{
    protected $table = 'publicaciones_foro';

    protected $fillable = [

        'autor_id',
        'categoria_id',
        'titulo',
        'contenido',
        'anonimo',
        'creado_en'
    ];


    //-------------RELACIONES-----------

    public function usuario(){
        return $this->belongsTo(Usuario::class);
    }

    public function categoria_foro(){
        return $this->belongsTo(Usuario::class);
    }
}
