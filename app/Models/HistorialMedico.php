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
        'fecha'
    ];

    //---------RELACIONES----------

    public function paciente(){
        return $this->belongsTo(Paciente::class);
    }

    public function cita(){
        return $this->belongsTo(Citas::class);
    }

    public function receta(){
        return $this->belongsTo(Receta::class);
    }

    public function usuario(){
        return $this->belongsTo(Usuario::class, 'creado_por');
    }

    public function archivos_adjuntos(){
        return $this->hasMany(ArchivosAdjuntos::class, 'historial_id');
    }
}
