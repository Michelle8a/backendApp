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
        Schema::create('paciente_enfermedad', function (Blueprint $table) {

            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('enfermedad_id')->constrained('enfermedades')->onDelete('cascade');
            
            $table->primary(['paciente_id', 'enfermedad_id']);

            $table->date('desde');
            $table->boolean('es_cronica')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paciente_enfermedad');
    }
};
