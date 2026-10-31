<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable implements JWTSubject
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


    //-----------JWT--------------------
    
    //el "sub" del token: identifica el usuario (su id)
    public function getJWTIdentifier()
    {
        return $this->getkey();
    }

    /**
     * claims propios que se guardan dentro del token.
     * OJO: el token va firmado pero NO cifrado, cualquiera puede leerlo
     * Por eso va el rol, nunca datos sensibles.
     */

    public function getJWTCustomClaims()
    {
        return [
            'rol' => $this->rol,
        ];
    }

    // HELPER DEL ROL
    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol, $roles, true);
    }
    
    
    
    
    //-----------RELACIONES-------------

    public function paciente(){
        return $this->hasOne(Paciente::class);
    }

    public function medico(){
        return $this->hasOne(Medico::class);
    }

    public function tokens_dispositivo(){
        return $this->hasMany(TokenDispositivo::class);
    }

    public function notificaciones(){
        return $this->hasMany(Notificacion::class);
    }

    public function bitacora_auditoria(){
        return $this->hasMany(BitacoraAuditoria::class);
    }

    public function publicaciones_foro(){
        return $this->hasMany(PublicacionForo::class);
    }
}