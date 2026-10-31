<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenDispositivo extends Model
{
    protected $table = 'tokens_dispositivo';

    protected $fillable = [

        'usuario_id',
        'token_fcm',
        'plataforma'
    ];


    //-----RELACIONES-----

    public function usuario(){
        return $this->belongsTo(Usuario::class);
    }
}
