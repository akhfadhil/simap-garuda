<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekap_headers', function (Blueprint $table) {
            $table->index(['jenis', 'tps_id'], 'rekap_headers_jenis_tps_idx');
        });

        Schema::table('rekap_partais', function (Blueprint $table) {
            $table->index(['jenis', 'dapil_id', 'nomor_urut'], 'rekap_partais_jenis_dapil_nomor_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rekap_partais', function (Blueprint $table) {
            $table->dropIndex('rekap_partais_jenis_dapil_nomor_idx');
        });

        Schema::table('rekap_headers', function (Blueprint $table) {
            $table->dropIndex('rekap_headers_jenis_tps_idx');
        });
    }
};
