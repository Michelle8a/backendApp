<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    protected $table = 'pacientes';

    protected $fillable = [

        'usuario_id', //tambien se ponen las llaves foraneas
        'idioma_id',
        'nombres',
        'apellidos',
        'dui_cifrado',
        'fecha_nacimiento',
        'sexo',
        'tipo_sangre',
        'direccion',
        'latitud',
        'longitud'
    ];

    public function usuario(){
        return $this->belongsTo(Usuario::class);
    }

    public function idioma(){
        return $this->belongsTo(Idioma::class);
    }
    
}
