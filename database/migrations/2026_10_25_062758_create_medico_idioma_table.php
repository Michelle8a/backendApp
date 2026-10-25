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
        Schema::create('medico_idioma', function (Blueprint $table) {
            
            $table->foreignId('medico_id')->constrained('medicos');
            $table->foreignId('idioma_id')->constrained('idiomas');

            $table->primary(['medico_id', 'idioma_id']);


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medico_idioma');
    }
};
