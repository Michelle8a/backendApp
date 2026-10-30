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
        Schema::create('paciente_alergia', function (Blueprint $table) {

            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('cascade');
            $table->foreignId('alergia_id')->constrained('alergias')->onDelete('restrict');

            $table->enum('gravedad', ['leve', 'moderada', 'grave']);

            $table->primary(['paciente_id', 'alergia_id']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paciente_alergia');
    }
};
