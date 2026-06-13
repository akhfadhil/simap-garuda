<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('kecamatan_id')->references('id')->on('kecamatans')->onDelete('set null');
            $table->foreign('desa_id')->references('id')->on('desas')->onDelete('set null');
            $table->foreign('tps_id')->references('id')->on('tps')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['kecamatan_id']);
            $table->dropForeign(['desa_id']);
            $table->dropForeign(['tps_id']);
        });
    }
};
