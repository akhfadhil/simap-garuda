<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_partais', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['dpr_ri', 'dprd_prov', 'dprd_kab']);
            $table->unsignedTinyInteger('nomor_urut');
            $table->string('nama_partai');
            $table->foreignId('dapil_id')->nullable()->constrained('dapils')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_partais');
    }
};
