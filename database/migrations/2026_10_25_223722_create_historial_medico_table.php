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
        Schema::create('historial_medico', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('restrict');
            $table->foreignId('cita_id')->nullable()->constrained('citas');
            $table->foreignId('receta_id')->nullable()->constrained('recetas');
            $table->foreignId('creado_por')->constrained('usuarios')->onDelete('restrict');

            $table->enum('tipo', ['consulta', 'receta', 'resultado', 'signos_vitales']);
            $table->text('diagnostico_cifrado');
            $table->datetime('fecha');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historial_medico');
    }
};
