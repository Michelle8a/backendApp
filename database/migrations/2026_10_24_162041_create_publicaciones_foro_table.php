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
        Schema::create('publicaciones_foro', function (Blueprint $table) {
            $table->id();

            $table->foreignId('autor_id')->constrained('usuarios')->onDelete('restrict');
            $table->foreignId('categoria_id')->constrained('categorias_foro')->onDelete('restrict');

            $table->string('titulo', 200);
            $table->text('contenido');
            $table->boolean('anonimo')->default(true);
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamps(); //quitar?
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('publicaciones_foro');
    }
};
