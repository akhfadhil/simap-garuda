<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekap_headers', function (Blueprint $table) {
            $table->index(['jenis', 'id', 'tps_id'], 'rekap_headers_jenis_id_tps_idx');
        });

        Schema::table('rekap_ppwp_suaras', function (Blueprint $table) {
            $table->index(['rekap_id', 'calon_id', 'suara'], 'rekap_ppwp_rekap_calon_suara_idx');
        });

        Schema::table('rekap_gubernur_suaras', function (Blueprint $table) {
            $table->index(['rekap_id', 'calon_id', 'suara'], 'rekap_gub_rekap_calon_suara_idx');
        });

        Schema::table('rekap_bupati_suaras', function (Blueprint $table) {
            $table->index(['rekap_id', 'calon_id', 'suara'], 'rekap_bup_rekap_calon_suara_idx');
        });

        Schema::table('rekap_dpd_suaras', function (Blueprint $table) {
            $table->index(['rekap_id', 'calon_id', 'suara'], 'rekap_dpd_rekap_calon_suara_idx');
        });

        Schema::table('rekap_partai_suaras', function (Blueprint $table) {
            $table->index(['rekap_id', 'partai_id', 'suara'], 'rekap_partai_rekap_partai_suara_idx');
        });

        Schema::table('rekap_caleg_suaras', function (Blueprint $table) {
            $table->index(['rekap_id', 'caleg_id', 'suara'], 'rekap_caleg_rekap_caleg_suara_idx');
        });
    }

    public function down(): void
    {
        Schema::table('rekap_caleg_suaras', function (Blueprint $table) {
            $table->dropIndex('rekap_caleg_rekap_caleg_suara_idx');
        });

        Schema::table('rekap_partai_suaras', function (Blueprint $table) {
            $table->dropIndex('rekap_partai_rekap_partai_suara_idx');
        });

        Schema::table('rekap_dpd_suaras', function (Blueprint $table) {
            $table->dropIndex('rekap_dpd_rekap_calon_suara_idx');
        });

        Schema::table('rekap_bupati_suaras', function (Blueprint $table) {
            $table->dropIndex('rekap_bup_rekap_calon_suara_idx');
        });

        Schema::table('rekap_gubernur_suaras', function (Blueprint $table) {
            $table->dropIndex('rekap_gub_rekap_calon_suara_idx');
        });

        Schema::table('rekap_ppwp_suaras', function (Blueprint $table) {
            $table->dropIndex('rekap_ppwp_rekap_calon_suara_idx');
        });

        Schema::table('rekap_headers', function (Blueprint $table) {
            $table->dropIndex('rekap_headers_jenis_id_tps_idx');
        });
    }
};
