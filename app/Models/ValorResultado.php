<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ValorResultado extends Model
{
    protected $table = 'valores_resultado';

    protected $fillable = [
        'resultado_id',
        'parametro',
        'valor',
        'unidad',
        'rango_referencia',
    ];

    //---------RELACIONES----------

    public function resultadoLaboratorio()
    {
        return $this->belongsTo(ResultadoLaboratorio::class, 'resultado_id');
    }
}
