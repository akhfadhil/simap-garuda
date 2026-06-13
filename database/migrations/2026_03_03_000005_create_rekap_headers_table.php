<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_headers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tps_id')->constrained('tps')->onDelete('cascade');
            $table->enum('jenis', ['ppwp', 'dpd', 'dpr_ri', 'dprd_prov', 'dprd_kab']);
            $table->enum('status', ['draft', 'final'])->default('draft');

            // Section I
            $table->unsignedInteger('dpt_lk')->default(0);
            $table->unsignedInteger('dpt_pr')->default(0);
            $table->unsignedInteger('pengguna_dpt_lk')->default(0);
            $table->unsignedInteger('pengguna_dpt_pr')->default(0);
            $table->unsignedInteger('pengguna_dptb_lk')->default(0);
            $table->unsignedInteger('pengguna_dptb_pr')->default(0);
            $table->unsignedInteger('pengguna_dpk_lk')->default(0);
            $table->unsignedInteger('pengguna_dpk_pr')->default(0);

            // Section II
            $table->unsignedInteger('ss_diterima')->default(0);
            $table->unsignedInteger('ss_digunakan')->default(0);
            $table->unsignedInteger('ss_rusak')->default(0);
            $table->unsignedInteger('ss_sisa')->default(0);

            // Section III
            $table->unsignedInteger('disabilitas_lk')->default(0);
            $table->unsignedInteger('disabilitas_pr')->default(0);

            // Section V
            $table->unsignedInteger('suara_sah')->default(0);
            $table->unsignedInteger('suara_tidak_sah')->default(0);

            // Meta
            $table->foreignId('diinput_oleh')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('difinalisasi_at')->nullable();

            $table->timestamps();
            $table->unique(['tps_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_headers');
    }
};