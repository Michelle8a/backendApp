<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversacion extends Model
{
    protected $table = 'conversaciones';

    protected $fillable = [
        'cita_id', 'creado_en',
        'paciente_id', 'tipo', 'estado_triaje',
        'especialidad_sugerida_id', 'resumen_triaje',
    ];

    //---------RELACIONES----------

    public function cita(){
        return $this->belongsTo(Citas::class, 'cita_id');
    }

    public function participantes(){
        return $this->belongsToMany(Usuario::class, 'participantes_conversacion', 'conversacion_id', 'usuario_id')
                    ->withTimestamps();
    }

    public function mensajes(){
        return $this->hasMany(Mensaje::class, 'conversacion_id');
    }
}
