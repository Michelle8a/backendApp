<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    protected $table = 'medicos';

    protected $fillable = [
        
        'usuario_id',
        'centro_salud_id',
        'jvpm',
        'nombres',
        'apellidos',
        'biografia',
        'tarifa',
        'calificacion_promedio',
        'verificado'

    ];

    //------------RELACIONES----------

    public function usuario(){
        return $this->belongsTo(Usuario::class);
    }

    public function centro_salud(){
        return $this->belongsTo(CentroSalud::class);
    }
}
