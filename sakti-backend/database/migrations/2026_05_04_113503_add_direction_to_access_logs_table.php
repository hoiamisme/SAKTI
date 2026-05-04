<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            // 'masuk' atau 'keluar' — ditentukan oleh kondisi presence kadet saat scan
            $table->enum('direction', ['masuk', 'keluar'])
                  ->nullable()
                  ->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropColumn('direction');
        });
    }
};
