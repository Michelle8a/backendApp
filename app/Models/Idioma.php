<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Idioma extends Model
{
    protected $table = 'idiomas';

    protected $fillable = [
        'nombre',
        'codigo'
    ];

    public $timestamps = false;



    //------------RELACIONES---------------

    public function pacientes(){
        return $this->hasMany(Paciente::class); //uno a muchos
    }

    public function medicos(){
        return $this->belongsToMany(Medico::class, 'medico_idioma', 'idioma_id', 'medico_id')->withTimestamps();
    }
}
