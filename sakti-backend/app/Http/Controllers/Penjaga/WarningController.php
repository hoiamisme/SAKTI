<?php
// =============================================================================
// File   : app/Http/Controllers/Penjaga/WarningController.php
// Fungsi : Daftar percobaan akses gagal hari ini — denied, face_mismatch,
//          rfid_unknown — disaring berdasarkan lokasi penjaga yang login.
//          Mendukung tandai dibaca / tandai semua dibaca.
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Penjaga;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WarningController extends Controller
{
    /** Halaman daftar peringatan */
    public function index(): View
    {
        $user = Auth::user();

        $warnings = AccessLog::with([
                'kadet:id,nama_lengkap,nim,foto_path',
                'location:id,nama_lokasi',
            ])
            ->whereIn('status', ['denied', 'face_mismatch', 'rfid_unknown'])
            ->whereDate('waktu_akses', today())
            ->when(
                $user->location_id,
                fn ($q) => $q->where('location_id', $user->location_id)
            )
            ->orderByDesc('waktu_akses')
            ->paginate(20);

        return view('penjaga.warnings.index', compact('warnings'));
    }

    /** AJAX — tandai satu peringatan sebagai dibaca */
    public function markRead(Request $request, AccessLog $log): JsonResponse
    {
        // Pastikan hanya warning dari lokasi penjaga yang bisa ditandai
        $user = Auth::user();
        if ($user->location_id && $log->location_id !== $user->location_id) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        $log->update(['is_read' => true]);

        // Kembalikan jumlah unread terbaru untuk update badge
        $unread = $this->unreadCount($user);

        return response()->json(['success' => true, 'unread' => $unread]);
    }

    /** AJAX — tandai semua peringatan hari ini sebagai dibaca */
    public function markAllRead(Request $request): JsonResponse
    {
        $user = Auth::user();

        AccessLog::whereIn('status', ['denied', 'face_mismatch', 'rfid_unknown'])
            ->whereDate('waktu_akses', today())
            ->where('is_read', false)
            ->when($user->location_id, fn ($q) => $q->where('location_id', $user->location_id))
            ->update(['is_read' => true]);

        return response()->json(['success' => true, 'unread' => 0]);
    }

    /** Helper — hitung unread milik penjaga ini */
    private function unreadCount($user): int
    {
        return AccessLog::whereIn('status', ['denied', 'face_mismatch', 'rfid_unknown'])
            ->whereDate('waktu_akses', today())
            ->where('is_read', false)
            ->when($user->location_id, fn ($q) => $q->where('location_id', $user->location_id))
            ->count();
    }
}
