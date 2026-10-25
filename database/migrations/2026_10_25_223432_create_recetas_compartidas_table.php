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
        Schema::create('recetas_compartidas', function (Blueprint $table) {

            $table->foreignId('receta_id')->constrained('recetas');
            $table->foreignId('farmacia_id')->constrained('farmacias');

            $table->primary(['receta_id', 'farmacia_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recetas_compartidas');
    }
};
