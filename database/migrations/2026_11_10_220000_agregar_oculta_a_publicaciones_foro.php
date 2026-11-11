<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Moderación: una publicación oculta deja de verse en la app pero se conserva en la BD.
     */
    public function up(): void
    {
        Schema::table('publicaciones_foro', function (Blueprint $table) {
            $table->boolean('oculta')->default(false)->after('anonimo');
            $table->timestamp('oculta_en')->nullable()->after('oculta');
            $table->foreignId('oculta_por')->nullable()->after('oculta_en')
                  ->constrained('usuarios')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('publicaciones_foro', function (Blueprint $table) {
            $table->dropForeign(['oculta_por']);
            $table->dropColumn(['oculta', 'oculta_en', 'oculta_por']);
        });
    }
};
