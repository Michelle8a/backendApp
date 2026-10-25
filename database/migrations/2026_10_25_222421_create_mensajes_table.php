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
        Schema::create('mensajes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('conversacion_id')->constrained('conversaciones')->cascadeOnDelete();
            $table->foreignId('emisor_id')->constrained('usuarios');

            $table->enum('tipo', ['texto', 'imagen', 'archivo', 'bot']);
            $table->text('contenido_cifrado');
            $table->string('adjunto_url')->nullable();
            $table->boolean('leido')->default(false);
            $table->timestamp('enviado_en')->useCurrent();
            
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mensajes');
    }
};
