<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HorarioMedico extends Model
{
    protected $table = 'horarios_medico';

    protected $fillable = [
        'medico_id',
        'dia_semana',
        'hora_inicio',
        'hora_fin',
    ];

    public function medico()
    {
        return $this->belongsTo(Medico::class);
    }
}
