<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tps_id')->nullable()->constrained('tps')->onDelete('cascade');
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatans')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->enum('jenis', ['PPWP', 'DPR_RI', 'DPD', 'DPRD_PROV', 'DPRD_KAB']);
            $table->enum('level', ['tps', 'kecamatan'])->default('tps');
            $table->enum('status', ['menunggu_verifikasi', 'terverifikasi', 'ditolak'])->default('menunggu_verifikasi');
            $table->text('komentar')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->timestamps();

            $table->unique(['tps_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumens');
    }
};
