<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->foreignId('cita_id')->nullable()->after('usuario_id')
                ->constrained('citas')->cascadeOnDelete();
            $table->json('datos')->nullable()->after('cuerpo');
            $table->index(['enviada_en', 'programada_para']);
        });

        Schema::table('notificaciones', function (Blueprint $table) {
            $table->enum('tipo', ['mensaje', 'recordatorio_cita', 'receta', 'cita'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('notificaciones', function (Blueprint $table) {
            $table->dropIndex(['enviada_en', 'programada_para']);
            $table->dropForeign(['cita_id']);
            $table->dropColumn(['cita_id', 'datos']);
        });
    }
};