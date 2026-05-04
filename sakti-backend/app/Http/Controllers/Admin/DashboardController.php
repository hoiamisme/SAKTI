<?php
// =============================================================================
// File   : app/Http/Controllers/Admin/DashboardController.php
// Fungsi : Dashboard admin — statistik harian + log akses terbaru
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Kadet;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = $this->buildStats();

        $recentLogs = AccessLog::with([
                'kadet:id,nama_lengkap,nim',
                'location:id,nama_lokasi',
            ])
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentLogs'));
    }

    /** AJAX — statistik + log terbaru untuk polling dashboard */
    public function stats(Request $request): JsonResponse
    {
        $afterId = $request->integer('after_id', 0);

        $logs = AccessLog::with([
                'kadet:id,nama_lengkap,nim',
                'location:id,nama_lokasi',
            ])
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id'          => $log->id,
                'waktu'       => $log->waktu_akses?->format('d/m H:i:s') ?? '-',
                'nama'        => $log->kadet?->nama_lengkap,
                'nim'         => $log->kadet?->nim,
                'lokasi'      => $log->location?->nama_lokasi ?? '-',
                'status'      => $log->status,
                'gambar_url'  => $log->gambar_path ? asset('storage/' . $log->gambar_path) : null,
            ]);

        return response()->json([
            'success' => true,
            'stats'   => $this->buildStats(),
            'logs'    => $logs,
        ]);
    }

    private function buildStats(): array
    {
        return [
            'total_kadets'     => Kadet::where('status', 'aktif')->count(),
            'total_locations'  => Location::where('is_active', true)->count(),
            'hari_ini_total'   => AccessLog::whereDate('waktu_akses', today())->count(),
            'hari_ini_granted' => AccessLog::whereDate('waktu_akses', today())
                ->where('status', 'granted')->count(),
            'hari_ini_denied'  => AccessLog::whereDate('waktu_akses', today())
                ->whereIn('status', ['denied', 'face_mismatch'])->count(),
            'rfid_unknown'     => AccessLog::whereDate('waktu_akses', today())
                ->where('status', 'rfid_unknown')->count(),
        ];
    }
}
