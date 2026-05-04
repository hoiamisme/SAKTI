<?php
// =============================================================================
// File   : app/Models/AccessLog.php
// Fungsi : Model log setiap percobaan akses (granted/denied/face_mismatch/dll)
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'kadet_id',
        'location_id',
        'rfid_uid',
        'waktu_akses',
        'status',
        'direction',
        'gambar_path',
        'keterangan',
        'is_read',
    ];

    protected $casts = [
        'waktu_akses' => 'datetime',
        'is_read'     => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    /** Kadet yang melakukan akses (nullable jika RFID tidak dikenal) */
    public function kadet(): BelongsTo
    {
        return $this->belongsTo(Kadet::class);
    }

    /** Lokasi / pos tempat akses terjadi */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeGranted($query)
    {
        return $query->where('status', 'granted');
    }

    public function scopeDenied($query)
    {
        return $query->whereIn('status', ['denied', 'face_mismatch', 'rfid_unknown']);
    }

    public function scopeByLocation($query, int $locationId)
    {
        return $query->where('location_id', $locationId);
    }
}
