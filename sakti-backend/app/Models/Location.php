<?php
// =============================================================================
// File   : app/Models/Location.php
// Fungsi : Model pos pemeriksaan / titik kontrol akses
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama_lokasi',
        'kode_lokasi',
        'location_type',
        'deskripsi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    /** Semua log akses yang terjadi di lokasi ini */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /** Semua permission yang terdaftar untuk lokasi ini */
    public function accessPermissions(): HasMany
    {
        return $this->hasMany(AccessPermission::class);
    }

    /** User penjaga yang ditugaskan di lokasi ini */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Ambil RTSP URL dari env berdasarkan kode_lokasi */
    public function getRtspUrl(): ?string
    {
        $key = 'RTSP_URL_' . strtoupper($this->kode_lokasi);

        return env($key);
    }
}
