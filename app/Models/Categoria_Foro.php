<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria_Foro extends Model
{
    protected $table = 'categorias_foro';

    protected $fillable = [

        'nombre'
    ];

    public $timestamps = false;
}
