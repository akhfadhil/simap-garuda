<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekap_cell_flags', function (Blueprint $table) {
            $table->string('row_key', 191)->change();
        });
    }

    public function down(): void
    {
        Schema::table('rekap_cell_flags', function (Blueprint $table) {
            $table->string('row_key', 96)->change();
        });
    }
};
