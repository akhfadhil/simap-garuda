<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE rekap_headers MODIFY COLUMN status ENUM('draft','perlu_dicek','final') NOT NULL DEFAULT 'draft'");
        } else {
            Schema::table('rekap_headers', function (Blueprint $table) {
                $table->enum('status', ['draft', 'perlu_dicek', 'final'])->default('draft')->change();
            });
        }

        Schema::table('rekap_headers', function (Blueprint $table) {
            $table->text('catatan_internal')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('rekap_headers', function (Blueprint $table) {
            $table->dropColumn('catatan_internal');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::table('rekap_headers')
                ->where('status', 'perlu_dicek')
                ->update(['status' => 'draft']);

            DB::statement("ALTER TABLE rekap_headers MODIFY COLUMN status ENUM('draft','final') NOT NULL DEFAULT 'draft'");
        } else {
            Schema::table('rekap_headers', function (Blueprint $table) {
                $table->enum('status', ['draft', 'final'])->default('draft')->change();
            });
        }
    }
};
