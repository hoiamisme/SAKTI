<?php
// =============================================================================
// File   : app/Http/Controllers/Admin/PresenceController.php
// Fungsi : Monitoring kehadiran kadet realtime — siapa ada di mana
// Author : SAKTI Dev Team
// Date   : 2026-05-04
// =============================================================================

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KadetPresence;
use App\Models\Kadet;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PresenceController extends Controller
{
    /** Halaman monitoring kehadiran */
    public function index(): View
    {
        $locations = Location::where('is_active', true)->orderBy('kode_lokasi')->get();
        $totalKadet = Kadet::where('status', 'aktif')->count();

        return view('admin.presence.index', compact('locations', 'totalKadet'));
    }

    /** AJAX — data kehadiran realtime */
    public function data(Request $request): JsonResponse
    {
        $locationId = $request->integer('location_id') ?: null;

        // Kadet yang saat ini berada di dalam area (current_location_id != null)
        $presencesQuery = KadetPresence::with([
            'kadet:id,nama_lengkap,nim,prodi,angkatan,foto_path,status',
            'currentLocation:id,nama_lokasi,kode_lokasi,location_type',
        ])->whereNotNull('current_location_id');

        if ($locationId) {
            $presencesQuery->where('current_location_id', $locationId);
        }

        $presences = $presencesQuery->orderBy('entered_at', 'desc')->get();

        $data = $presences->map(fn ($p) => [
            'kadet_id'      => $p->kadet_id,
            'nama_lengkap'  => $p->kadet?->nama_lengkap ?? '-',
            'nim'           => $p->kadet?->nim ?? '-',
            'prodi'         => $p->kadet?->prodi ?? '-',
            'foto_url'      => $p->kadet?->foto_path
                ? asset('storage/' . $p->kadet->foto_path)
                : null,
            'lokasi'        => $p->currentLocation?->nama_lokasi ?? '-',
            'lokasi_type'   => $p->currentLocation?->location_type ?? '-',
            'entered_at'    => $p->entered_at?->format('d/m/Y H:i:s'),
            'durasi'        => $p->entered_at
                ? $p->entered_at->diffForHumans(now(), true)
                : '-',
        ]);

        // Ringkasan per lokasi
        $summary = KadetPresence::whereNotNull('current_location_id')
            ->with('currentLocation:id,nama_lokasi,location_type')
            ->get()
            ->groupBy('current_location_id')
            ->map(fn ($group, $locId) => [
                'location_id'  => $locId,
                'nama_lokasi'  => $group->first()?->currentLocation?->nama_lokasi ?? '-',
                'location_type'=> $group->first()?->currentLocation?->location_type ?? '-',
                'jumlah'       => $group->count(),
            ])
            ->values();

        return response()->json([
            'success'       => true,
            'total_inside'  => $presences->count(),
            'presences'     => $data,
            'summary'       => $summary,
            'generated_at'  => now()->format('H:i:s'),
        ]);
    }
}
