<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TokenDispositivo extends Model
{
    protected $table = 'tokens_dispositivo';

    protected $fillable = [

        'usuario_id',
        'token_fcm',
        'plataforma'
    ];


    //-----RELACIONES-----

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
