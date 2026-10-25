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
        Schema::create('citas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('medico_id')->constrained('medicos')->onDelete('cascade');
            $table->datetime('fecha_hora');
            $table->integer('duracion_min');
            $table->enum('tipo', ['general','especialista']);
            $table->enum('estado', ['pendiente', 'confirmada', 'en_curso', 'cancelada', 'finalizada'])->default('pendiente');
            $table->string('motivo');
            $table->string('sala_video');
            $table->text('notas_medico_cifradas');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
