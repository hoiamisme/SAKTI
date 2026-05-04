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
        Schema::table('kadets', function (Blueprint $table) {
            // Simpan foto wajah sebagai base64 JPEG langsung di DB
            // agar face recognition Python bisa akses tanpa perlu storage lokal
            $table->mediumText('face_image')->nullable()->after('face_encoding')
                  ->comment('Base64 JPEG foto wajah untuk ArcFace (dari webcam)');
        });
    }

    public function down(): void
    {
        Schema::table('kadets', function (Blueprint $table) {
            $table->dropColumn('face_image');
        });
    }
};
