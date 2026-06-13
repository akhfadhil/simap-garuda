<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_calegs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('partai_id')->constrained('rekap_partais')->onDelete('cascade');
            $table->unsignedTinyInteger('nomor_urut');
            $table->string('nama_caleg');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_calegs');
    }
};
