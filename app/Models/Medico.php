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
        'es_general',
        'biografia',
        'tarifa',
        'calificacion_promedio',
        'verificado',
    ];

    //para que la API devuelva true/false y numeros en vez de 1/0 y texto
    protected $casts = [
        'es_general' => 'boolean',
        'verificado' => 'boolean',
        'tarifa' => 'float',
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