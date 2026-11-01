<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespuestaAdmision extends Model
{
    protected $table = 'respuestas_admision';

    protected $fillable = [
        'sesion_id',
        'clave',
        'pregunta',
        'respuesta',
        'respondida_en'
    ];

    //---------RELACIONES----------

    public function sesion(){
        return $this->belongsTo(SesionAdmision::class);
    }
}
