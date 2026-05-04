<?php
// =============================================================================
// File   : app/Http/Controllers/Penjaga/DashboardController.php
// Fungsi : Dashboard penjaga pos — menampilkan log akses di pos yang dijaga
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Penjaga;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\KadetPresence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $user       = Auth::user();
        $locationId = $user->location_id;

        $recentLogs = AccessLog::with(['kadet', 'location'])
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        // Ambil posisi terkini untuk setiap kadet yang muncul di log
        $kadetIds = $recentLogs->pluck('kadet_id')->filter()->unique()->values();
        $presences = KadetPresence::with('currentLocation:id,nama_lokasi,location_type')
            ->whereIn('kadet_id', $kadetIds)
            ->get()
            ->keyBy('kadet_id');

        $stats = $this->buildStats($locationId);

        return view('penjaga.dashboard', compact('recentLogs', 'stats', 'user', 'presences'));
    }

    /** AJAX — statistik + log terbaru untuk polling dashboard */
    public function stats(Request $request): JsonResponse
    {
        $user       = Auth::user();
        $locationId = $user->location_id;
        $afterId    = $request->integer('after_id', 0);

        $rawLogs = AccessLog::with(['kadet:id,nama_lengkap,nim', 'location:id,nama_lokasi'])
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        // Ambil posisi terkini untuk kadet di log ini
        $kadetIds = $rawLogs->pluck('kadet_id')->filter()->unique()->values();
        $presences = KadetPresence::with('currentLocation:id,nama_lokasi,location_type')
            ->whereIn('kadet_id', $kadetIds)
            ->get()
            ->keyBy('kadet_id');

        $logs = $rawLogs->map(function ($log) use ($presences) {
            $presence   = $log->kadet_id ? ($presences[$log->kadet_id] ?? null) : null;
            $posisiText = null;
            if ($presence && $presence->current_location_id !== null) {
                $loc = $presence->currentLocation;
                $posisiText = $loc
                    ? ($loc->location_type === 'gate'
                        ? 'Di dalam ksatrian'
                        : 'Di ' . $loc->nama_lokasi)
                    : 'Di dalam';
            } else {
                $posisiText = 'Di luar';
            }

            return [
                'id'         => $log->id,
                'waktu'      => $log->waktu_akses?->format('d/m H:i:s') ?? '-',
                'nama'       => $log->kadet?->nama_lengkap,
                'nim'        => $log->kadet?->nim,
                'lokasi'     => $log->location?->nama_lokasi ?? '-',
                'status'     => $log->status,
                'posisi'     => $posisiText,
                'is_inside'  => $presence && $presence->current_location_id !== null,
                'gambar_url' => $log->gambar_path ? asset('storage/' . $log->gambar_path) : null,
            ];
        });

        return response()->json([
            'success' => true,
            'stats'   => $this->buildStats($locationId),
            'logs'    => $logs,
        ]);
    }

    private function buildStats(?int $locationId): array
    {
        $base = fn () => AccessLog::when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->whereDate('waktu_akses', today());

        return [
            'hari_ini_total'   => (clone $base())->count(),
            'hari_ini_granted' => (clone $base())->where('status', 'granted')->count(),
            'hari_ini_denied'  => (clone $base())->whereIn('status', ['denied', 'face_mismatch', 'rfid_unknown'])->count(),
        ];
    }
}
