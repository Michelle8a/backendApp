<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Enfermedad extends Model
{
    protected $table = 'enfermedades';

    protected $fillable = [
        'nombre'
    ];

    public $timestamps = false;


    //---------RELACIONES----------

    public function pacientes(){ //Tabla Paciente - Enfermedad
        return $this->belongsToMany(Paciente::class, 'paciente_enfermedad', 'enfermedad_id', 'paciente_id')
                    ->withPivot('desde', 'es_cronica')
                    ->withTimestamps();
    }
}
