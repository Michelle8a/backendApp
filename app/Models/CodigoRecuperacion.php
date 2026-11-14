<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodigoRecuperacion extends Model
{
    protected $table = 'codigos_recuperacion';

    protected $fillable = ['usuario_id', 'codigo_hash', 'expira_en', 'intentos', 'usado_en'];

    protected $hidden = ['codigo_hash'];

    protected $casts = [
        'expira_en' => 'datetime',
        'usado_en'  => 'datetime',
        'intentos'  => 'integer',
    ];
}