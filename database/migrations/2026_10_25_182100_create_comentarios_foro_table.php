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
        Schema::create('comentarios_foro', function (Blueprint $table) {
            $table->id();

            $table->foreignId('publicacion_id')->constrained('publicaciones_foro');
            $table->foreignId('autor_id')->constrained('usuarios');

            $table->text('contenido');
            $table->timestamp('creado_en');

            $table->timestamps(); //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comentarios_foro');
    }
};
