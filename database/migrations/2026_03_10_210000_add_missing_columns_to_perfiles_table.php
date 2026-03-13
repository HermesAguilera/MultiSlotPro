<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perfiles', function (Blueprint $table) {
            if (! Schema::hasColumn('perfiles', 'cliente_codigo_pais')) {
                $table->string('cliente_codigo_pais', 6)->default('504')->after('cliente_nombre');
            }
        });

        Schema::table('perfiles', function (Blueprint $table) {
            if (! Schema::hasColumn('perfiles', 'cliente_telefono')) {
                $table->string('cliente_telefono', 30)->nullable()->after('cliente_codigo_pais');
            }
        });
    }

    public function down(): void
    {
        Schema::table('perfiles', function (Blueprint $table) {
            if (Schema::hasColumn('perfiles', 'cliente_codigo_pais')) {
                $table->dropColumn('cliente_codigo_pais');
            }
            if (Schema::hasColumn('perfiles', 'cliente_telefono')) {
                $table->dropColumn('cliente_telefono');
            }
        });
    }
};
