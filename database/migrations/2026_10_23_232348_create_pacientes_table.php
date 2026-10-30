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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')->constrained('usuarios')->unique()->onDelete('restrict');
            $table->foreignId('idioma_id')->constrained('idiomas')->onDelete('restrict');

            $table->string('nombres', 100);
            $table->string('apellidos', 100);
            $table->text('dui_cifrado');
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F', 'Otro']);
            $table->string('tipo_sangre', 3);
            $table->string('direccion');
            $table->decimal('latitud', 10, 7);
            $table->decimal('longitud', 10, 7);
            $table->timestamps();

            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
