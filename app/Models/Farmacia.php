<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Farmacia extends Model
{
    protected $table = 'farmacias';

    protected $fillable = [

        'nombre',
        'direccion',
        'latitud',
        'longitud',
        'telefono',
        'correo'
    ];

    public $timestamps = false;
}
