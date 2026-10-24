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
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('cascade');

            $table->enum('tipo', ['mensaje', 'recordatorio_cita', 'receta']);
            $table->string('titulo', 150);
            $table->text('cuerpo');
            $table->boolean('leida')->default(false);
            $table->dateTime('programada_para');
            $table->dateTime('enviada_en');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
