<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Codigo_Verificacion extends Model
{
    protected $table = 'codigos_verificacion';

    protected $fillable = [
        'usuario_id',
        'codigo_hash',
        'expira_en',
        'intentos',
    ];

    //------RELACIONES---------

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
