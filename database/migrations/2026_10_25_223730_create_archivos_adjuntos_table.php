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
        Schema::create('archivos_adjuntos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')->constrained('pacientes')->onDelete('restrict');
            $table->foreignId('historial_id')->constrained('historial_medico')->nullable();
            $table->foreignId('sesion_id')->constrained('sesiones_admision')->nullable();

            $table->string('url');
            $table->enum('tipo', ['imagen', 'video', 'pdf']);
            $table->timestamp('subido_en');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos_adjuntos');
    }
};
