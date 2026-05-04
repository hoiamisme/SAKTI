<?php
// =============================================================================
// File   : app/Models/KadetPresence.php
// Fungsi : Menyimpan posisi/keberadaan kadet secara realtime
//          null = di luar (belum scan gerbang masuk)
//          current_location_id = ID lokasi tempat kadet berada
// Author : SAKTI Dev Team
// Date   : 2026-05-04
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KadetPresence extends Model
{
    use HasFactory;

    protected $fillable = [
        'kadet_id',
        'current_location_id',
        'entered_at',
    ];

    protected $casts = [
        'entered_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function kadet(): BelongsTo
    {
        return $this->belongsTo(Kadet::class);
    }

    public function currentLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'current_location_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** True jika kadet berada di dalam area (sudah melewati gerbang) */
    public function isInsideArea(): bool
    {
        return $this->current_location_id !== null;
    }

    /** True jika kadet sedang di dalam ruangan (bukan sekadar sudah masuk gerbang) */
    public function isInsideRoom(): bool
    {
        return $this->isInsideArea()
            && $this->currentLocation
            && $this->currentLocation->location_type === 'room';
    }
}
