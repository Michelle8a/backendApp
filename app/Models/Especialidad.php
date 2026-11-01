<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Especialidad extends Model
{
    protected $table = 'especialidades';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    public $timestamps = false;



    //-----RELACIONES-----

    public function medicos(){
        return $this->belongsToMany(Medico::class, 'medico_especialidad', 'especialidad_id', 'medico_id')->withTimestamps();
    }
}
