<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetalleReceta extends Model
{
    protected $table = 'detalles_receta';

    protected $fillable = [
        'receta_id',
        'medicamento',
        'dosis',
        'frecuencia',
        'duracion',
        'indicaciones'
    ];

    //-------RELACIONES-----------

    public function receta(){
        return $this->belongsTo(Receta::class, 'receta_id');
    }

}
