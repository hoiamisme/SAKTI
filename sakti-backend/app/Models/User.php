<?php
// =============================================================================
// File   : app/Models/User.php
// Fungsi : Model user (admin & penjaga pos) dengan relasi ke Location
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'location_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    /** Pos yang dijaga oleh user penjaga */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPenjaga(): bool
    {
        return $this->role === 'penjaga';
    }
}
