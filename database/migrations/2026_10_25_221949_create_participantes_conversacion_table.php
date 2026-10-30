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
        Schema::create('participantes_conversacion', function (Blueprint $table) {
            
            $table->foreignId('conversacion_id')->constrained('conversaciones')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('restrict');

            $table->primary(['conversacion_id', 'usuario_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('participantes_conversacion');
    }
};
