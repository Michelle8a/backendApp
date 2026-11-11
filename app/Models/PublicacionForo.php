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

    //los campos de moderacion (oculta, oculta_en, oculta_por) NO van en fillable
    //a proposito: solo los toca el panel admin con forceFill
    protected $casts = [
        'oculta'    => 'boolean',
        'oculta_en' => 'datetime',
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

    //admin que la oculto
    public function moderador(){
        return $this->belongsTo(Usuario::class, 'oculta_por');
    }
}
