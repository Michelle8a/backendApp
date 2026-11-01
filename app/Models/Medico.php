<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Medico extends Model
{
    protected $table = 'medicos';

    protected $fillable = [
        
        'usuario_id',
        'centro_salud_id',
        'jvpm',
        'nombres',
        'apellidos',
        'biografia',
        'tarifa',
        'calificacion_promedio',
        'verificado'

    ];

    //------------RELACIONES----------

    public function usuario(){
        return $this->belongsTo(Usuario::class);
    }

    public function centro_salud(){
        return $this->belongsTo(CentroSalud::class);
    }

    public function especialidades(){
        return $this->belongsToMany(Especialidad::class, 'medico_especialidad', 'medico_id', 'especialidad_id')->withTimestamps();
    }

    public function idiomas(){
        return $this->belongsToMany(Idioma::class, 'medico_idioma', 'medico_id', 'idioma_id')->withTimestamps();
    }

    public function horarios(){
        return $this->hasMany(HorarioMedico::class);
    }

    public function citas(){
        return $this->hasMany(Citas::class, 'medico_id');
    }

    public function recetas(){
        return $this->hasMany(Receta::class, 'medico_id');
    }
}
