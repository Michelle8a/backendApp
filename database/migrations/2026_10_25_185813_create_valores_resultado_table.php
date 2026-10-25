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
        Schema::create('valores_resultado', function (Blueprint $table) {
            $table->id();

            $table->foreignId('resultado_id')->constrained('resultados_laboratorio')->onDelete('cascade');

            $table->string('parametro', 100);
            $table->decimal('valor', 10, 2);
            $table->string('unidad', 20);
            $table->string('rango_referencia', 50);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('valores_resultado');
    }
};
