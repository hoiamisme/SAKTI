<?php
// =============================================================================
// File   : app/Http/Controllers/Admin/ReportController.php
// Fungsi : Laporan log akses — filter tanggal, lokasi, kadet, dan status
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\Location;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'dari_tanggal' => ['nullable', 'date'],
            'sampai_tanggal' => ['nullable', 'date', 'after_or_equal:dari_tanggal'],
            'location_id'  => ['nullable', 'exists:locations,id'],
            'status'       => ['nullable', 'in:granted,denied,face_mismatch,rfid_unknown'],
        ]);

        $query = AccessLog::with(['kadet', 'location'])
            ->orderByDesc('waktu_akses');

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('waktu_akses', '>=', $request->dari_tanggal);
        }

        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('waktu_akses', '<=', $request->sampai_tanggal);
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs      = $query->paginate(25)->withQueryString();
        $locations = Location::orderBy('kode_lokasi')->get();

        // Statistik ringkasan untuk periode yang difilter
        $stats = [
            'total'         => $query->toBase()->count(),
            'granted'       => (clone $query)->where('status', 'granted')->toBase()->count(),
            'denied'        => (clone $query)->where('status', 'denied')->toBase()->count(),
            'face_mismatch' => (clone $query)->where('status', 'face_mismatch')->toBase()->count(),
            'rfid_unknown'  => (clone $query)->where('status', 'rfid_unknown')->toBase()->count(),
        ];

        return view('admin.reports.index', compact('logs', 'locations', 'stats'));
    }

    public function exportPdf(Request $request): Response
    {
        $request->validate([
            'dari_tanggal'  => ['nullable', 'date'],
            'sampai_tanggal'=> ['nullable', 'date', 'after_or_equal:dari_tanggal'],
            'location_id'   => ['nullable', 'exists:locations,id'],
            'status'        => ['nullable', 'in:granted,denied,face_mismatch,rfid_unknown'],
        ]);

        $query = AccessLog::with(['kadet', 'location'])
            ->orderByDesc('waktu_akses');

        if ($request->filled('dari_tanggal')) {
            $query->whereDate('waktu_akses', '>=', $request->dari_tanggal);
        }
        if ($request->filled('sampai_tanggal')) {
            $query->whereDate('waktu_akses', '<=', $request->sampai_tanggal);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $logs = $query->get();

        $stats = [
            'total'         => $logs->count(),
            'granted'       => $logs->where('status', 'granted')->count(),
            'denied'        => $logs->where('status', 'denied')->count(),
            'face_mismatch' => $logs->where('status', 'face_mismatch')->count(),
            'rfid_unknown'  => $logs->where('status', 'rfid_unknown')->count(),
        ];

        $lokasiNama  = $request->filled('location_id')
            ? (Location::find($request->location_id)?->nama_lokasi ?? 'Semua')
            : 'Semua';

        $statusLabel = match($request->status) {
            'granted'       => 'Diberikan',
            'denied'        => 'Ditolak',
            'face_mismatch' => 'Wajah Tidak Cocok',
            'rfid_unknown'  => 'RFID Tidak Dikenal',
            default         => 'Semua',
        };

        $dari   = $request->dari_tanggal;
        $sampai = $request->sampai_tanggal;

        // Pre-encode setiap snapshot ke base64 data URI agar dompdf dapat menampilkannya
        $snapBase64 = [];
        foreach ($logs as $log) {
            if ($log->gambar_path) {
                $absPath = storage_path('app/public/' . $log->gambar_path);
                if (file_exists($absPath)) {
                    $mime = mime_content_type($absPath) ?: 'image/jpeg';
                    $snapBase64[$log->id] = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($absPath));
                }
            }
        }

        $pdf = Pdf::loadView('admin.reports.pdf',
            compact('logs', 'stats', 'dari', 'sampai', 'lokasiNama', 'statusLabel', 'snapBase64')
        )->setPaper('a4', 'landscape');

        $filename = 'laporan-akses-' . now()->format('Ymd_His') . '.pdf';

        return $pdf->download($filename);
    }
}
