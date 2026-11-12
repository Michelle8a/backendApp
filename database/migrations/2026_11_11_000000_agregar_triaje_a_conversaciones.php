<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // La admision ocurre ANTES de que exista la cita
        Schema::table('conversaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('cita_id')->nullable()->change();

            $table->foreignId('paciente_id')->nullable()->after('cita_id')
                ->constrained('pacientes')->onDelete('restrict');
            $table->enum('tipo', ['consulta', 'triaje'])->default('consulta')->after('paciente_id');
            $table->enum('estado_triaje', ['en_curso', 'completado', 'urgente'])->nullable()->after('tipo');
            $table->foreignId('especialidad_sugerida_id')->nullable()->after('estado_triaje')
                ->constrained('especialidades')->nullOnDelete();
            $table->text('resumen_triaje')->nullable()->after('especialidad_sugerida_id'); // cifrado
        });

        // Los mensajes del bot no tienen usuario emisor
        Schema::table('mensajes', function (Blueprint $table) {
            $table->unsignedBigInteger('emisor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('conversaciones', function (Blueprint $table) {
            $table->dropConstrainedForeignId('especialidad_sugerida_id');
            $table->dropConstrainedForeignId('paciente_id');
            $table->dropColumn(['tipo', 'estado_triaje', 'resumen_triaje']);
        });
    }
};