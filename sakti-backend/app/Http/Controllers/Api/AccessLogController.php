<?php
// =============================================================================
// File   : app/Http/Controllers/Api/AccessLogController.php
// Fungsi : Menerima data akses dari Python (RFID + face recognition + snapshot)
//          dan menyimpannya ke database. Autentikasi via X-API-KEY header.
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AccessLogRequest;
use App\Models\AccessLog;
use App\Models\Kadet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessLogController extends Controller
{
    // -------------------------------------------------------------------------
    // POST /api/access-log
    // -------------------------------------------------------------------------

    /**
     * Simpan log akses baru yang dikirim oleh Python worker.
     */
    public function store(AccessLogRequest $request): JsonResponse
    {
        // --- Validasi API Key ---
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey || $apiKey !== config('app.api_secret_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. API key tidak valid.',
            ], 401);
        }

        // --- Upload snapshot CCTV jika ada ---
        $gambarPath = null;

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $tahun  = now()->format('Y');
            $bulan  = now()->format('m');
            $folder = config('app.snapshot_storage_path') . "/{$tahun}/{$bulan}";

            $gambarPath = $request->file('image')->store($folder, 'public');
        }

        // --- Cari kadet berdasarkan RFID (bisa NULL jika tidak terdaftar) ---
        $kadet = Kadet::where('rfid_uid', $request->rfid_uid)->first();

        // --- Simpan log ---
        $log = AccessLog::create([
            'kadet_id'    => $kadet?->id,
            'location_id' => $request->location_id,
            'rfid_uid'    => $request->rfid_uid,
            'waktu_akses' => $request->waktu_akses ?? now(),
            'status'      => $request->status,
            'gambar_path' => $gambarPath,
            'keterangan'  => $request->keterangan,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Log akses berhasil disimpan.',
            'data'    => [
                'id'          => $log->id,
                'rfid_uid'    => $log->rfid_uid,
                'location_id' => $log->location_id,
                'status'      => $log->status,
                'waktu_akses' => $log->waktu_akses,
                'gambar_url'  => $gambarPath
                    ? asset('storage/' . $gambarPath)
                    : null,
            ],
        ], 201);
    }

    // -------------------------------------------------------------------------
    // GET /api/kadets/{rfid_uid}
    // -------------------------------------------------------------------------

    /**
     * Ambil data kadet berdasarkan RFID UID.
     * Digunakan oleh Python worker sebelum proses face recognition.
     */
    public function findByRfid(Request $request, string $rfidUid): JsonResponse
    {
        // --- Validasi API Key ---
        $apiKey = $request->header('X-API-KEY');

        if (!$apiKey || $apiKey !== config('app.api_secret_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. API key tidak valid.',
            ], 401);
        }

        $kadet = Kadet::where('rfid_uid', $rfidUid)
            ->where('status', 'aktif')
            ->select([
                'id', 'nama_lengkap', 'nim', 'prodi',
                'angkatan', 'rfid_uid', 'face_encoding',
                'foto_path', 'face_image', 'status',
            ])
            ->first();

        if (!$kadet) {
            return response()->json([
                'success' => false,
                'message' => 'Kadet dengan RFID tersebut tidak ditemukan atau tidak aktif.',
                'data'    => null,
            ], 404);
        }

        // Tampilkan face_image untuk Python worker (face recognition)
        $kadet->makeVisible(['face_image']);

        return response()->json([
            'success' => true,
            'message' => 'Kadet ditemukan.',
            'data'    => $kadet,
        ], 200);
    }
}
