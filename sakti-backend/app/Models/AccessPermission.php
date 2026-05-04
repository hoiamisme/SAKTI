<?php
// =============================================================================
// File   : app/Models/AccessPermission.php
// Fungsi : Model hak akses kadet per lokasi/pos
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'kadet_id',
        'location_id',
        'is_allowed',
    ];

    protected $casts = [
        'is_allowed' => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function kadet(): BelongsTo
    {
        return $this->belongsTo(Kadet::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
