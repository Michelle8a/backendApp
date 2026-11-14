<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialMedico extends Model
{
    protected $table = 'historial_medico';

    protected $fillable = [
        'paciente_id',
        'cita_id',
        'receta_id',
        'creado_por',
        'tipo',
        'disgnostico_cifrado',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'datetime',
    ];

    // Nunca sale el cifrado en un toArray()/JSON
    protected $hidden = ['diagnostico_cifrado'];

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function cita()
    {
        return $this->belongsTo(Citas::class, 'cita_id');
    }

    public function receta()
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }

    public function creador()
    {
        return $this->belongsTo(Usuario::class, 'creado_por');
    }
}