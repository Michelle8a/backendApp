<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaForo extends Model
{
    protected $table = 'categorias_foro';

    protected $fillable = [

        'nombre'
    ];

    public $timestamps = false;
}
