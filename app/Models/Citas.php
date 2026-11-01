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

    public function paciente()
    {
        return $this->belongsTo(Pacientes::class, 'paciente_id');
    }

    public function medico()
    {
        return $this->belongsTo(Medicos::class, 'medico_id');
    }
}
