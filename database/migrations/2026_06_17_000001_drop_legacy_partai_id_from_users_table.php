<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'partai_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('partai_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'partai_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('partai_id')->nullable()->after('tps_id');
        });
    }
};
