<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'paciente_id',
        'medico_id',
        'fecha_hora',
        'duracion_min',
        'tipo',
        'estado',
        'motivo',
        'sala_video',
        'notas_medico_cifradas'
    ];



    //-------RELACIONES-----------

    public function paciente(){
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function medico(){
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function sesion_admision(){
        return $this->hasOne(SesionAdmision::class, 'cita_id');
    }

    public function conversacion(){
        return $this->hasOne(Conversacion::class, 'cita_id');
    }

    public function receta(){
        return $this->hasOne(Receta::class, 'cita_id');
    }

    public function historial_medico(){
        return $this->hasMany(HistorialMedico::class, 'cita_id');
    }
}
