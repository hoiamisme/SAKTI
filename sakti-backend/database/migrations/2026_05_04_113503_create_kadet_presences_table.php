<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kadet_presences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kadet_id')
                  ->unique()
                  ->constrained('kadets')
                  ->cascadeOnDelete();
            // null = di luar (belum masuk gerbang)
            $table->foreignId('current_location_id')
                  ->nullable()
                  ->constrained('locations')
                  ->nullOnDelete();
            $table->timestamp('entered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kadet_presences');
    }
};
