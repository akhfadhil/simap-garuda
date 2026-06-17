<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'rekap_ppwp_suaras',
            'rekap_gubernur_suaras',
            'rekap_bupati_suaras',
            'rekap_dpd_suaras',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        foreach ([
            'rekap_ppwp_calons',
            'rekap_gubernur_calons',
            'rekap_bupati_calons',
            'rekap_dpd_calons',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('rekap_ppwp_calons')) {
            Schema::create('rekap_ppwp_calons', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('nomor_urut');
                $table->string('nama_paslon');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rekap_gubernur_calons')) {
            Schema::create('rekap_gubernur_calons', function (Blueprint $table) {
                $table->id();
                $table->integer('nomor_urut');
                $table->string('nama_paslon', 200);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rekap_bupati_calons')) {
            Schema::create('rekap_bupati_calons', function (Blueprint $table) {
                $table->id();
                $table->integer('nomor_urut');
                $table->string('nama_paslon', 200);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rekap_dpd_calons')) {
            Schema::create('rekap_dpd_calons', function (Blueprint $table) {
                $table->id();
                $table->unsignedTinyInteger('nomor_urut');
                $table->string('nama_calon');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('rekap_ppwp_suaras')) {
            Schema::create('rekap_ppwp_suaras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rekap_id')->constrained('rekap_headers')->onDelete('cascade');
                $table->foreignId('calon_id')->constrained('rekap_ppwp_calons')->onDelete('cascade');
                $table->unsignedInteger('suara')->default(0);
                $table->timestamps();
                $table->unique(['rekap_id', 'calon_id']);
                $table->index(['rekap_id', 'calon_id', 'suara'], 'rekap_ppwp_rekap_calon_suara_idx');
            });
        }

        if (! Schema::hasTable('rekap_gubernur_suaras')) {
            Schema::create('rekap_gubernur_suaras', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rekap_id');
                $table->unsignedBigInteger('calon_id');
                $table->unsignedInteger('suara')->default(0);
                $table->timestamps();
                $table->foreign('rekap_id')->references('id')->on('rekap_headers')->onDelete('cascade');
                $table->foreign('calon_id')->references('id')->on('rekap_gubernur_calons')->onDelete('cascade');
                $table->unique(['rekap_id', 'calon_id']);
                $table->index(['rekap_id', 'calon_id', 'suara'], 'rekap_gub_rekap_calon_suara_idx');
            });
        }

        if (! Schema::hasTable('rekap_bupati_suaras')) {
            Schema::create('rekap_bupati_suaras', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rekap_id');
                $table->unsignedBigInteger('calon_id');
                $table->unsignedInteger('suara')->default(0);
                $table->timestamps();
                $table->foreign('rekap_id')->references('id')->on('rekap_headers')->onDelete('cascade');
                $table->foreign('calon_id')->references('id')->on('rekap_bupati_calons')->onDelete('cascade');
                $table->unique(['rekap_id', 'calon_id']);
                $table->index(['rekap_id', 'calon_id', 'suara'], 'rekap_bup_rekap_calon_suara_idx');
            });
        }

        if (! Schema::hasTable('rekap_dpd_suaras')) {
            Schema::create('rekap_dpd_suaras', function (Blueprint $table) {
                $table->id();
                $table->foreignId('rekap_id')->constrained('rekap_headers')->onDelete('cascade');
                $table->foreignId('calon_id')->constrained('rekap_dpd_calons')->onDelete('cascade');
                $table->unsignedInteger('suara')->default(0);
                $table->timestamps();
                $table->unique(['rekap_id', 'calon_id']);
                $table->index(['rekap_id', 'calon_id', 'suara'], 'rekap_dpd_rekap_calon_suara_idx');
            });
        }
    }
};
