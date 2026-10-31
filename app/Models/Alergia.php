<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alergia extends Model
{
    protected $table = 'alergias';

    protected $fillable = [
        'nombre'
    ];

    public $timestamps = false;


    //------RELACIONES---------

    public function pacientes(){ //Tabla compuesta Paciente - Alergia
        return $this->belongsToMany(Paciente::class, 'paciente_alergia', 'alergia_id', 'paciente_id')
                    ->withPivot('gravedad')
                    ->withTimestamps();
    }
}
