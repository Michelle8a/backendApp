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
        return $this->belongsTo(Usuario::class, 'autor_id');
    }

    public function categoria_foro(){
        return $this->belongsTo(CategoriaForo::class, 'categoria_id');
    }

    public function comentarios(){
        return $this->hasMany(ComentariosForo::class, 'publicacion_id');
    }

    public function me_gusta(){
        return $this->belongsToMany(Usuario::class, 'me_gusta_foro', 'publicacion_id', 'autor_id')->withTimestamps();
    }
}
