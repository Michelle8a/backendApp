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
        Schema::create('medico_especialidad', function (Blueprint $table) {
            
            $table->foreach('medico_id')->references('id')->on('medicos')->onDelete('cascade');
            $table->foreach('especialidad_id')->references('id')->on('especialidades')->onDelete('cascade');

            $table->primary(['medico_id', 'especialidad_id']);
        
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medico_especialidad');
    }
};
