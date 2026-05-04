<?php
// =============================================================================
// File   : app/Models/Kadet.php
// Fungsi : Model data kadet (RFID UID, face encoding, status)
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Kadet extends Model
{
    use HasFactory;

    protected $table = 'kadets';

    protected $fillable = [
        'nama_lengkap',
        'nim',
        'prodi',
        'angkatan',
        'rfid_uid',
        'face_encoding',
        'face_image',
        'foto_path',
        'status',
    ];

    protected $hidden = [
        'face_encoding', // Jangan ekspose encoding ke response umum
        'face_image',    // Jangan ekspose raw base64 ke response umum
    ];

    protected $casts = [
        'face_encoding' => 'array',
        'angkatan'      => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    /** Semua log akses milik kadet ini */
    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /** Semua permission akses milik kadet ini */
    public function accessPermissions(): HasMany
    {
        return $this->hasMany(AccessPermission::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isAktif(): bool
    {
        return $this->status === 'aktif';
    }

    /** Cek apakah kadet diizinkan akses ke lokasi tertentu */
    public function isAllowedAt(int $locationId): bool
    {
        return $this->accessPermissions()
            ->where('location_id', $locationId)
            ->where('is_allowed', true)
            ->exists();
    }
}
