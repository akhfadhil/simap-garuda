<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_ppwp_suaras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('rekap_headers')->onDelete('cascade');
            $table->foreignId('calon_id')->constrained('rekap_ppwp_calons')->onDelete('cascade');
            $table->unsignedInteger('suara')->default(0);
            $table->timestamps();
            $table->unique(['rekap_id', 'calon_id']);
        });

        Schema::create('rekap_gubernur_suaras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rekap_id');
            $table->unsignedBigInteger('calon_id');
            $table->unsignedInteger('suara')->default(0);
            $table->timestamps();

            $table->foreign('rekap_id')
                ->references('id')
                ->on('rekap_headers')
                ->onDelete('cascade');
            $table->foreign('calon_id')
                ->references('id')
                ->on('rekap_gubernur_calons')
                ->onDelete('cascade');
            $table->unique(['rekap_id', 'calon_id']);
        });

        Schema::create('rekap_bupati_suaras', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rekap_id');
            $table->unsignedBigInteger('calon_id');
            $table->unsignedInteger('suara')->default(0);
            $table->timestamps();

            $table->foreign('rekap_id')
                ->references('id')
                ->on('rekap_headers')
                ->onDelete('cascade');
            $table->foreign('calon_id')
                ->references('id')
                ->on('rekap_bupati_calons')
                ->onDelete('cascade');
            $table->unique(['rekap_id', 'calon_id']);
        });

        Schema::create('rekap_dpd_suaras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('rekap_headers')->onDelete('cascade');
            $table->foreignId('calon_id')->constrained('rekap_dpd_calons')->onDelete('cascade');
            $table->unsignedInteger('suara')->default(0);
            $table->timestamps();
            $table->unique(['rekap_id', 'calon_id']);
        });

        Schema::create('rekap_partai_suaras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('rekap_headers')->onDelete('cascade');
            $table->foreignId('partai_id')->constrained('rekap_partais')->onDelete('cascade');
            $table->unsignedInteger('suara')->default(0);
            $table->timestamps();
            $table->unique(['rekap_id', 'partai_id']);
        });

        Schema::create('rekap_caleg_suaras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rekap_id')->constrained('rekap_headers')->onDelete('cascade');
            $table->foreignId('caleg_id')->constrained('rekap_calegs')->onDelete('cascade');
            $table->unsignedInteger('suara')->default(0);
            $table->timestamps();
            $table->unique(['rekap_id', 'caleg_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_caleg_suaras');
        Schema::dropIfExists('rekap_partai_suaras');
        Schema::dropIfExists('rekap_dpd_suaras');
        Schema::dropIfExists('rekap_bupati_suaras');
        Schema::dropIfExists('rekap_gubernur_suaras');
        Schema::dropIfExists('rekap_ppwp_suaras');
    }
};
