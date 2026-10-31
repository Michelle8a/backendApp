<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignosVitales extends Model
{
    protected $table = 'signos_vitales';

    protected $fillable = [

        'paciente_id',
        'presion_sistolica',
        'presion_diastolica',
        'peso_kg',
        'glucosa', 
        'temperatura',
        'medido_en'
    ];

    
    //-------RELACIONES------

    public function paciente(){
        return $this->belongsTo(Paciente::class);
    }
}
