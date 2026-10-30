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
        Schema::create('resultados_laboratorio', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_paciente')->constrained('pacientes')->onDelete('restrict');

            $table->string('tipo_prueba', 120);
            $table->string('archivo_url');
            $table->date('fecha');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resultados_laboratorio');
    }
};
