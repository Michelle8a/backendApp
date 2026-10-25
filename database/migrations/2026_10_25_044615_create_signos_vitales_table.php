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
        Schema::create('signos_vitales', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')->constrained('pacientes');

            $table->integer('presion_sistolica');
            $table->integer('presion_diastolica');
            $table->decimal('peso_kg', 5, 2);
            $table->decimal('glucosa', 5, 1);
            $table->decimal('temperatura', 4, 1);
            $table->datetime('medido_en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('signos_vitales');
    }
};
