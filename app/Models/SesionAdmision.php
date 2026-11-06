<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SesionAdmision extends Model
{
    protected $table = 'sesiones_admision';

    protected $fillable = [
        'cita_id',
        'paciente_id',
        'estado',
        'nivel_urgencia',
        'resumen_para_medico',
        'completada_en',
    ];

    //-------RELACIONES-----------

    public function cita(){
        return $this->belongsTo(Citas::class, 'cita_id');
    }

    public function paciente(){
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function respuestas(){
        return $this->hasMany(RespuestaAdmision::class, 'sesion_id');
    }

    public function bandera_roja(){
        return $this->hasOne(BanderaRoja::class, 'cita_id', 'cita_id');
    }

    public function archivos_adjuntos(){
        return $this->hasMany(ArchivosAdjuntos::class, 'sesion_id');
    }
}
