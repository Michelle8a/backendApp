<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MODIFY es exclusivo de MySQL. En SQLite (pruebas) el esquema ya es compatible.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE citas MODIFY notas_medico_cifradas TEXT NULL");
            DB::statement("ALTER TABLE citas MODIFY estado ENUM('pendiente','confirmada','en_curso','cancelada','finalizada','no_asistio') NOT NULL DEFAULT 'pendiente'");
        }

        Schema::table('citas', function (Blueprint $table) {
            $table->foreignId('cancelada_por')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->string('motivo_cancelacion')->nullable();
            $table->timestamp('cancelada_en')->nullable();
            $table->timestamp('finalizada_en')->nullable();

            $table->decimal('monto', 8, 2)->default(0);
            $table->enum('pago_estado', ['gratis', 'pendiente', 'pagado', 'reembolso_pendiente', 'reembolsado'])->default('gratis');
            $table->timestamp('pagada_en')->nullable();
        });

        // Ajusta la lista a lo que ya tengas en mensajes.tipo (existe 'bot')
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE mensajes MODIFY tipo ENUM('texto','imagen','archivo','audio','bot','sistema') NOT NULL");
        }
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cancelada_por');
            $table->dropColumn(['motivo_cancelacion', 'cancelada_en', 'finalizada_en', 'monto', 'pago_estado', 'pagada_en']);
        });
    }
};