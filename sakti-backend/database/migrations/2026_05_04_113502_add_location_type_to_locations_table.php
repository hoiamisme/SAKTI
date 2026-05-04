<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // 'gate' = gerbang utama (akses masuk/keluar area)
            // 'room' = ruangan (lab, dll) — memerlukan sudah masuk gate
            $table->enum('location_type', ['gate', 'room'])
                  ->default('room')
                  ->after('kode_lokasi');
        });

        // Set Gerbang Ksatrian sebagai gate, sisanya room
        DB::table('locations')->where('kode_lokasi', 'Pos 1')->update(['location_type' => 'gate']);
    }

    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('location_type');
        });
    }
};
