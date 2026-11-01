<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResultadoLaboratorio extends Model
{
    protected $table = 'resultados_laboratorio';

    protected $fillable = [
        'id_paciente',
        'tipo_prueba',
        'archivo_url',
        'fecha',
    ];


    //---------RELACIONES----------

    public function paciente()
    {
        return $this->belongsTo(Paciente::class, 'id_paciente');
    }

    public function valoresResultado()
    {
        return $this->hasMany(ValorResultado::class, 'resultado_id');
    }
}
