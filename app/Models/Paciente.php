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

    //---------RELACIONES----------

    public function usuario(){
        return $this->belongsTo(Usuario::class);
    }

    public function idioma(){
        return $this->belongsTo(Idioma::class);
    }

    public function contactos_emergencia(){
        return $this->hasMany(ContactoEmergencia::class);
    }

    public function alergias(){ //Tabla compuesta Paciente - Alergia
        return $this->belongsToMany(Alergia::class, 'paciente_alergia', 'paciente_id', 'alergia_id')
                ->withPivot('gravedad')
                ->withTimestamps();
    }

    public function enfermedades(){ //Tabla Paciente - Enfermedad
        return $this->belongsToMany(Enfermedad::class, 'paciente_enfermedad', 'paciente_id', 'enfermedad_id')
                    ->withPivot('desde', 'es_cronica')
                    ->withTimestamps();
    }

    public function signosVitales(){
        return $this->hasMany(SignosVitales::class);
    }

    public function resultadosLaboratorio(){
        return $this->hasMany(ResultadoLaboratorio::class, 'id_paciente');
    }

    public function citas(){
        return $this->hasMany(Citas::class, 'paciente_id');
    }

    public function sesionesAdmision(){
        return $this->hasMany(SesionAdmision::class, 'paciente_id');
    }

    public function recetas(){
        return $this->hasMany(Receta::class, 'paciente_id');
    }

    public function historialMedico(){
        return $this->hasMany(HistorialMedico::class, 'paciente_id');
    }

    public function archivosAdjuntos(){
        return $this->hasMany(ArchivosAdjuntos::class, 'paciente_id');
    }

}
