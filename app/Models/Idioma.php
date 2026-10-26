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
}
