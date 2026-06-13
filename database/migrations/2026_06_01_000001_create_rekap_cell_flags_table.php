<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekap_cell_flags', function (Blueprint $table) {
            $table->id();
            $table->string('jenis', 32);
            $table->string('level', 32);
            $table->unsignedBigInteger('entity_id');
            $table->string('row_key', 191);
            $table->foreignId('flagged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['jenis', 'level', 'entity_id', 'row_key'], 'rekap_cell_flags_unique_cell');
            $table->index(['jenis', 'level', 'entity_id'], 'rekap_cell_flags_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekap_cell_flags');
    }
};
