<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekap_partai_suaras', function (Blueprint $table) {
            $table->index(['partai_id', 'rekap_id', 'suara'], 'rekap_partai_partai_rekap_suara_idx');
        });

        Schema::table('rekap_caleg_suaras', function (Blueprint $table) {
            $table->index(['caleg_id', 'rekap_id', 'suara'], 'rekap_caleg_caleg_rekap_suara_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rekap_caleg_suaras', function (Blueprint $table) {
            $table->dropIndex('rekap_caleg_caleg_rekap_suara_idx');
        });

        Schema::table('rekap_partai_suaras', function (Blueprint $table) {
            $table->dropIndex('rekap_partai_partai_rekap_suara_idx');
        });
    }
};
