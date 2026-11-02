<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bitacora_auditoria', function (Blueprint $table) {
            // Acciones sin un registro concreto (ej. "ver listado") no tienen recurso_id
            $table->unsignedBigInteger('recurso_id')->nullable()->change();

            // La fecha se llena sola al insertar
            $table->timestamp('fecha')->useCurrent()->change();

            // "fecha" ya cumple el papel de created_at: se eliminan created_at y updated_at
            $table->dropTimestamps();

            // Índices para los filtros de la API
            $table->index('fecha');
            $table->index(['recurso', 'recurso_id']);
        });
    }

    public function down(): void
    {
        Schema::table('bitacora_auditoria', function (Blueprint $table) {
            $table->dropIndex(['fecha']);
            $table->dropIndex(['recurso', 'recurso_id']);
            $table->timestamps();
        });
    }
};