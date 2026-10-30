<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;

    protected $table = 'usuarios';

    protected $fillable = [
        'correo',
        'contrasena_hash',
        'rol',
        'telefono',
        'consentimiento_aceptado',
        'consentimiento_fecha',
        'consentimiento_version',
    ];

    //para que no salga en las respuestas json
    protected $hidden = ['contrasena_hash'];

    //porque la contraseña esta en contrasena_hash y no en password
    public function getAuthPassword()
    {
        return $this->contrasena_hash;
    }

    //-----------RELACIONES-------------

    public function paciente()
    {
        return $this->hasOne(Paciente::class);
    }

    public function medico()
    {
        return $this->hasOne(Medico::class);
    }
}