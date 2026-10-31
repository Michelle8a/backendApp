<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactoEmergencia extends Model
{
    protected $table = 'contactos_emergencia';

    protected $fillable = [

        'paciente_id',
        'nombre',
        'telefono',
        'parentesco'
    ];


    //------RELACIONES------

    public function paciente(){
        return $this->belongsTo(Paciente::class);
    }
}
