<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_ppwp_calons', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('nomor_urut');
            $table->string('nama_paslon');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_ppwp_calons');
    }
};
