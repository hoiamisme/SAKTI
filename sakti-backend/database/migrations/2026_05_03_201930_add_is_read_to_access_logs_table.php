<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            // Hanya warning (denied, face_mismatch, rfid_unknown) yang perlu
            // ditandai dibaca/belum. Default false = belum dibaca.
            $table->boolean('is_read')->default(false)->after('gambar_path');
        });
    }

    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropColumn('is_read');
        });
    }
};
