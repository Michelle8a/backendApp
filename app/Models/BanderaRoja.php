<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BanderaRoja extends Model
{
    protected $table = 'banderas_rojas';

    protected $fillable = [
        'cita_id',
        'creado_en'
    ];

    public $timestamps = false;

    //-------RELACIONES-----------

    public function sesion_admision(){
        return $this->belongsTo(SesionAdmision::class, 'cita_id', 'cita_id');
    }
}
