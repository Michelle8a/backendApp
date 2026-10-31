<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CentroSalud extends Model
{
    protected $table = 'centros_salud';

    protected $fillable = [
        'nombre',
        'tipo',
        'direccion',
        'latitud',
        'longitud',
        'telefono'
    ];

    public $timestamps = false;


    //-----RELACIONES------
    
    public function medicos(){
        return $this->hasMany(Medico::class);
    }
}
