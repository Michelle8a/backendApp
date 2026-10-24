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
        Schema::create('bitacora_auditoria', function (Blueprint $table) {
            $table->id();

            $table->foreignId('usuario_id')->constrained('usuarios')->onDelete('restrict');

            $table->string('accion', 80);
            $table->string('recurso', 80);
            $table->unsignedBigInteger('recurso_id');
            $table->string('ip', 45);
            $table->timestamp('fecha');
            $table->timestamps(); //quitar despues

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_auditoria');
    }
};
