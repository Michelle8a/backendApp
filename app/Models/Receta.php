<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receta extends Model
{
    protected $table = 'recetas';

    protected $fillable = [
        'codigo',
        'paciente_id',
        'medico_id',
        'cita_id',
        'estado',
        'firma_digital',
        'pdf_url'
    ];

    //---------RELACIONES----------

    public function paciente(){
        return $this->belongsTo(Paciente::class, 'paciente_id');
    }

    public function medico(){
        return $this->belongsTo(Medico::class, 'medico_id');
    }

    public function cita(){
        return $this->belongsTo(Citas::class, 'cita_id');
    }

    public function detalles(){
        return $this->hasMany(DetalleReceta::class, 'receta_id');
    }


    public function historial_medico(){
        return $this->hasMany(HistorialMedico::class, 'receta_id');
    }

    public function farmacias_compartidas(){
        return $this->belongsToMany(Farmacia::class, 'recetas_compartidas', 'receta_id', 'farmacia_id')
                    ->withTimestamps();
    }
}
