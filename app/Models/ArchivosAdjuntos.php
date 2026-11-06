<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ArchivosAdjuntos extends Model
{
    protected $table = 'archivos_adjuntos';

    protected $fillable = [

        'paciente_id',
        'historial_id',
        'sesion_id',
        'url',
        'tipo',
        'subido_en'
    ];

    protected $casts = [
        'subido_en' => 'datetime',
    ];

    //---------RELACIONES----------

    public function historial_medico(){
        return $this->belongsTo(HistorialMedico::class);
    }

    public function sesion_admision(){
        return $this->belongsTo(SesionAdmision::class);
    }

    public function paciente(){
        return $this->belongsTo(Paciente::class);
    }
}
