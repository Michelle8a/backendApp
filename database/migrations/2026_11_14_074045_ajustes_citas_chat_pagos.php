<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. citas: notas opcionales y estado nuevo 'no_asistio' (se puede repetir sin problema)
        DB::statement("ALTER TABLE citas MODIFY notas_medico_cifradas TEXT NULL");
        DB::statement("ALTER TABLE citas MODIFY estado ENUM('pendiente','confirmada','en_curso','cancelada','finalizada','no_asistio') NOT NULL DEFAULT 'pendiente'");

        // 2. citas: cancelación, cierre, pago y reprogramación (solo lo que falte)
        Schema::table('citas', function (Blueprint $table) {
            if (! Schema::hasColumn('citas', 'cancelada_por')) {
                $table->foreignId('cancelada_por')->nullable()->constrained('usuarios')->nullOnDelete();
            }
            if (! Schema::hasColumn('citas', 'motivo_cancelacion')) {
                $table->string('motivo_cancelacion')->nullable();
            }
            if (! Schema::hasColumn('citas', 'cancelada_en')) {
                $table->timestamp('cancelada_en')->nullable();
            }
            if (! Schema::hasColumn('citas', 'finalizada_en')) {
                $table->timestamp('finalizada_en')->nullable();
            }
            if (! Schema::hasColumn('citas', 'monto')) {
                $table->decimal('monto', 8, 2)->default(0);
            }
            if (! Schema::hasColumn('citas', 'pago_estado')) {
                $table->enum('pago_estado', ['gratis', 'pendiente', 'pagado', 'reembolso_pendiente', 'reembolsado'])->default('gratis');
            }
            if (! Schema::hasColumn('citas', 'pagada_en')) {
                $table->timestamp('pagada_en')->nullable();
            }
            if (! Schema::hasColumn('citas', 'reprogramada_de')) {
                $table->foreignId('reprogramada_de')->nullable()->constrained('citas')->nullOnDelete();
            }
        });

        // 3. 'cita' y 'sistema' ya no dependen de un ENUM
        DB::statement("ALTER TABLE notificaciones MODIFY tipo VARCHAR(30) NOT NULL");
        DB::statement("ALTER TABLE mensajes MODIFY tipo VARCHAR(20) NOT NULL");
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            if (Schema::hasColumn('citas', 'reprogramada_de')) {
                $table->dropConstrainedForeignId('reprogramada_de');
            }
            if (Schema::hasColumn('citas', 'cancelada_por')) {
                $table->dropConstrainedForeignId('cancelada_por');
            }
            $table->dropColumn(array_values(array_filter(
                ['motivo_cancelacion', 'cancelada_en', 'finalizada_en', 'monto', 'pago_estado', 'pagada_en'],
                fn ($c) => Schema::hasColumn('citas', $c)
            )));
        });
    }
};