<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Citas extends Model
{
    protected $table = 'citas';

    protected $fillable = [
        'paciente_id', 'medico_id', 'fecha_hora', 'duracion_min', 'tipo', 'estado',
        'motivo', 'sala_video', 'notas_medico_cifradas',
        'cancelada_por', 'motivo_cancelacion', 'cancelada_en', 'finalizada_en',
        'monto', 'pago_estado', 'pagada_en',
        'reprogramada_de',
    ];

    protected $casts = [
        'fecha_hora'    => 'datetime',
        'cancelada_en'  => 'datetime',
        'finalizada_en' => 'datetime',
        'pagada_en'     => 'datetime',
    ];

    public const MARGEN_ANTES_MIN = 5;   // el chat abre 5 min antes
    public const TOPE_EXTRA_MIN   = 30;  // tope duro despues de la hora de fin

    public function aperturaChat(): Carbon
    {
        return $this->fecha_hora->copy()->subMinutes(self::MARGEN_ANTES_MIN);
    }

    public function topeChat(): Carbon
    {
        return $this->fecha_hora->copy()->addMinutes($this->duracion_min + self::TOPE_EXTRA_MIN);
    }

    /** esperando_confirmacion | programado | abierto | cerrado */
    public function estadoChat(): string
    {
        if (in_array($this->estado, ['cancelada', 'no_asistio', 'finalizada'], true)) {
            return 'cerrado';
        }
        if ($this->estado === 'pendiente') {
            return 'esperando_confirmacion';
        }
        $ahora = now();
        if ($ahora->lt($this->aperturaChat())) return 'programado';
        if ($ahora->gt($this->topeChat()))     return 'cerrado';
        return 'abierto';
    }

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