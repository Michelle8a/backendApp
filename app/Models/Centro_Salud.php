<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Centro_Salud extends Model
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
}
