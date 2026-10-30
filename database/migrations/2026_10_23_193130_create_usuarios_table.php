<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            
            $table->id();
            $table->string('correo', 150)->unique();
            $table->string('contrasena_hash');
            $table->enum('rol', ['admin', 'paciente', 'medico'])->default('paciente');
            $table->string('telefono', 20);
            $table->boolean('consentimiento_aceptado');
            $table->dateTime('consentimiento_fecha');
            $table->string('consentimiento_version', 10);
            $table->enum('estado', ['activo', 'suspendido'])->default('activo');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};
