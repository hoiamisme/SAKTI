<?php
// =============================================================================
// File   : app/Http/Controllers/Penjaga/ScanController.php
// Fungsi : Tampilan monitoring scan RFID real-time untuk penjaga pos
//          Mengambil log terbaru via polling (AJAX setiap N detik)
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Penjaga;

use App\Http\Controllers\Controller;
use App\Models\AccessLog;
use App\Models\AccessPermission;
use App\Models\Kadet;
use App\Models\KadetPresence;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Process\Process;
use Illuminate\View\View;

class ScanController extends Controller
{
    /** Halaman utama monitoring scan */
    public function index(): View
    {
        $user       = Auth::user();
        $locationId = $user->location_id;
        $locations  = Location::where('is_active', true)->orderBy('kode_lokasi')->get();

        return view('penjaga.scan', compact('locationId', 'locations'));
    }

    /**
     * Endpoint AJAX — lookup UID langsung dari input bar di halaman scan.
     * Dipanggil browser ketika penjaga mengetik / RFID reader tap.
     * Membuat log untuk kasus rfid_unknown dan denied (non-aktif).
     * Kasus granted → tidak buat log, dilanjutkan ke verifyFace().
     */
    public function lookup(Request $request): JsonResponse
    {
        $request->validate([
            'rfid_uid'    => ['required', 'string', 'max:50'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $uid        = strtoupper(trim($request->input('rfid_uid')));
        $locationId = $request->integer('location_id') ?: Auth::user()->location_id;
        $kadet      = Kadet::where('rfid_uid', $uid)->first();

        if (! $kadet) {
            AccessLog::create([
                'kadet_id'    => null,
                'location_id' => $locationId,
                'rfid_uid'    => $uid,
                'waktu_akses' => now(),
                'status'      => 'rfid_unknown',
                'keterangan'  => "UID '{$uid}' tidak terdaftar di database.",
            ]);

            return response()->json([
                'success' => true,
                'status'  => 'rfid_unknown',
                'uid'     => $uid,
                'kadet'   => null,
                'pesan'   => "UID '{$uid}' tidak terdaftar di database.",
            ]);
        }

        $fotoUrl = $kadet->foto_path
            ? asset('storage/' . $kadet->foto_path)
            : 'https://ui-avatars.com/api/?name=' . urlencode($kadet->nama_lengkap) . '&size=80&background=1a1a2e&color=e94560';

        $kadetPayload = [
            'nama_lengkap' => $kadet->nama_lengkap,
            'nim'          => $kadet->nim,
            'prodi'        => $kadet->prodi,
            'angkatan'     => $kadet->angkatan,
            'status'       => $kadet->status,
            'foto_url'     => $fotoUrl,
            'has_face'     => ! empty($kadet->face_image),
        ];

        if ($kadet->status !== 'aktif') {
            $pesan = "Kadet {$kadet->nama_lengkap} berstatus non-aktif. Akses ditolak.";
            AccessLog::create([
                'kadet_id'    => $kadet->id,
                'location_id' => $locationId,
                'rfid_uid'    => $uid,
                'waktu_akses' => now(),
                'status'      => 'denied',
                'keterangan'  => $pesan,
            ]);

            return response()->json([
                'success' => true,
                'status'  => 'denied',
                'uid'     => $uid,
                'kadet'   => $kadetPayload,
                'pesan'   => $pesan,
            ]);
        }

        // Cek hak akses kadet ke lokasi ini (whitelist — harus ada record is_allowed=true)
        $hasPermission = AccessPermission::where('kadet_id', $kadet->id)
            ->where('location_id', $locationId)
            ->where('is_allowed', true)
            ->exists();

        if (! $hasPermission) {
            $location = Location::find($locationId);
            $namaLok  = $location ? $location->nama_lokasi : "Lokasi #{$locationId}";
            $pesan    = "Kadet {$kadet->nama_lengkap} tidak memiliki hak akses ke {$namaLok}.";
            AccessLog::create([
                'kadet_id'    => $kadet->id,
                'location_id' => $locationId,
                'rfid_uid'    => $uid,
                'waktu_akses' => now(),
                'status'      => 'denied',
                'keterangan'  => $pesan,
            ]);

            return response()->json([
                'success' => true,
                'status'  => 'denied',
                'uid'     => $uid,
                'kadet'   => $kadetPayload,
                'pesan'   => $pesan,
            ]);
        }

        // Kadet aktif + punya hak akses → lanjut ke verifikasi wajah (log dibuat di verifyFace)
        // Sertakan info keberadaan kadet saat ini agar penjaga tahu status sebelum scan wajah
        $presence   = KadetPresence::with('currentLocation')->where('kadet_id', $kadet->id)->first();
        $currLokasi = $presence?->currentLocation?->nama_lokasi;
        $isInside   = $presence && $presence->current_location_id !== null;

        return response()->json([
            'success'     => true,
            'status'      => 'pending_face',
            'uid'         => $uid,
            'location_id' => $locationId,
            'kadet'       => $kadetPayload,
            'presence'    => [
                'is_inside'       => $isInside,
                'lokasi_sekarang' => $currLokasi,
            ],
            'pesan'       => "Kadet {$kadet->nama_lengkap} ditemukan. Lanjut verifikasi wajah.",
        ]);
    }

    /**
     * Endpoint AJAX — verifikasi wajah via face service (Flask).
     * Dipanggil setelah lookup berhasil dan kadet berstatus aktif.
     * Membuat access log dengan status granted / face_mismatch.
     */
    public function verifyFace(Request $request): JsonResponse
    {
        $request->validate([
            'rfid_uid'    => ['required', 'string', 'max:50'],
            'face_image'  => ['required', 'string'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
        ]);

        $uid        = strtoupper(trim($request->input('rfid_uid')));
        $faceB64    = $request->input('face_image');
        $locationId = $request->integer('location_id');

        $kadet = Kadet::where('rfid_uid', $uid)->first();
        if (! $kadet) {
            return response()->json([
                'success' => false,
                'message' => "Kadet dengan UID '{$uid}' tidak ditemukan.",
            ], 404);
        }

        // Double-check hak akses (seharusnya sudah dicek di lookup(), ini safety net)
        $hasPermission = AccessPermission::where('kadet_id', $kadet->id)
            ->where('location_id', $locationId)
            ->where('is_allowed', true)
            ->exists();

        if (! $hasPermission) {
            $location = Location::find($locationId);
            $namaLok  = $location ? $location->nama_lokasi : "Lokasi #{$locationId}";
            $pesan    = "Kadet {$kadet->nama_lengkap} tidak memiliki hak akses ke {$namaLok}.";
            AccessLog::create([
                'kadet_id'    => $kadet->id,
                'location_id' => $locationId,
                'rfid_uid'    => $uid,
                'waktu_akses' => now(),
                'status'      => 'denied',
                'keterangan'  => $pesan,
            ]);
            return response()->json([
                'success'    => true,
                'status'     => 'denied',
                'match'      => false,
                'confidence' => 0,
                'keterangan' => $pesan,
                'snapshot_url' => null,
                'kadet' => [
                    'nama_lengkap' => $kadet->nama_lengkap,
                    'nim'          => $kadet->nim,
                    'prodi'        => $kadet->prodi,
                    'foto_url'     => $kadet->foto_path
                        ? asset('storage/' . $kadet->foto_path)
                        : 'https://ui-avatars.com/api/?name=' . urlencode($kadet->nama_lengkap) . '&size=80&background=1a1a2e&color=e94560',
                ],
            ]);
        }

        // Panggil face_verify.py langsung via Python (tidak butuh service terpisah)
        $pythonBin  = env('PYTHON_PATH', 'python');
        $scriptPath = env('FACE_VERIFY_SCRIPT',
            base_path('..') . DIRECTORY_SEPARATOR . 'sakti-python' . DIRECTORY_SEPARATOR . 'face_verify.py'
        );

        // Sertakan face_image referensi langsung dari DB ke stdin Python.
        // Hal ini menghindari deadlock: php artisan serve single-threaded sehingga
        // Python tidak bisa call-back ke API Laravel (/api/v1/kadets/{rfid}) saat
        // PHP sedang blocked menunggu Process::run().
        $kadet->makeVisible(['face_image']);
        $faceImageRef = $kadet->face_image; // base64 JPEG dari DB (atau null)

        $process = new Process([$pythonBin, $scriptPath]);
        $process->setInput(json_encode([
            'rfid_uid'        => $uid,
            'face_image_b64'  => $faceB64,
            'face_image_ref'  => $faceImageRef,  // referensi dari DB, langsung tanpa API call
        ]));
        $process->setTimeout(60); // TF cold-start bisa 20-30 detik pertama kali
        $process->setEnv(['PYTHONIOENCODING' => 'utf-8', 'PYTHONUNBUFFERED' => '1']);
        $process->run();

        if (! $process->isSuccessful() || trim($process->getOutput()) === '') {
            // Bersihkan stderr — bisa mengandung encoding non-UTF-8 dari TF/Windows
            $stderrClean = mb_convert_encoding($process->getErrorOutput(), 'UTF-8', 'UTF-8');
            $stderrClean = preg_replace('/[^\x09\x0A\x0D\x20-\x7E]/u', '', (string) $stderrClean);
            \Illuminate\Support\Facades\Log::error('face_verify.py error', [
                'exit_code' => $process->getExitCode(),
                'stderr'    => $process->getErrorOutput(),
                'stdout'    => $process->getOutput(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Verifikasi wajah gagal dijalankan. Periksa log server.',
            ], 503);
        }

        // Ekstrak substring JSON dari output (safety net jika ada sisa noise di stdout)
        $rawOutput = $process->getOutput();
        $jsonStart = strpos($rawOutput, '{');
        $jsonEnd   = strrpos($rawOutput, '}');
        $jsonStr   = ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart)
            ? substr($rawOutput, $jsonStart, $jsonEnd - $jsonStart + 1)
            : $rawOutput;

        $faceResult = json_decode($jsonStr, true);
        if (! is_array($faceResult)) {
            \Illuminate\Support\Facades\Log::error('face_verify.py bad output', [
                'raw' => $rawOutput,
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Output face_verify.py tidak valid.',
            ], 503);
        }

        $match      = (bool) ($faceResult['match'] ?? false);
        $confidence = round((float) ($faceResult['confidence'] ?? 0.0) * 100, 1);
        $keterangan = $faceResult['keterangan'] ?? '';

        // Tentukan status final
        $status = $match ? 'granted' : 'face_mismatch';

        // -----------------------------------------------------------------------
        // Panggil cctv_capture.py secara TERPISAH untuk mengambil snapshot CCTV.
        // Script ini fokus hanya pada RTSP capture, independen dari face recognition.
        // -----------------------------------------------------------------------
        $gambarPath   = null;
        $cctvScript   = env('CCTV_CAPTURE_SCRIPT',
            base_path('..') . DIRECTORY_SEPARATOR . 'sakti-python' . DIRECTORY_SEPARATOR . 'cctv_capture.py'
        );

        try {
            // Fix: Spawn via cmd.exe /c untuk mendapatkan fresh Windows process
            // context yang tidak mewarisi state Winsock bermasalah dari PHP web server.
            // Input ditulis ke temp file karena cmd.exe /c tidak support piping stdin
            // secara reliable dari proc_open di semua versi Windows.
            $tmpInput  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sakti_cctv_' . uniqid() . '.json';
            $tmpOutput = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sakti_cctv_' . uniqid() . '.json';

            file_put_contents($tmpInput, json_encode([
                'rfid_uid' => $uid,
                'status'   => $status,
            ]));

            $pyBin  = str_replace('/', DIRECTORY_SEPARATOR, $pythonBin);
            $script = str_replace('/', DIRECTORY_SEPARATOR, $cctvScript);

            // Build cmd: python cctv_capture.py < input.json > output.json
            $cmdLine = sprintf(
                'cmd.exe /c ""%s" "%s" < "%s" > "%s" 2>nul"',
                $pyBin, $script, $tmpInput, $tmpOutput
            );

            $startTs = microtime(true);
            exec($cmdLine, $execDummy, $execExit);
            $elapsed = round((microtime(true) - $startTs), 2);

            $cctvRaw    = file_exists($tmpOutput) ? file_get_contents($tmpOutput) : '';
            $cctvExit   = $execExit;
            $cctvStderr = '(stderr suppressed — see python side log)';

            @unlink($tmpInput);
            @unlink($tmpOutput);

            \Illuminate\Support\Facades\Log::info('cctv_capture result', [
                'exit'        => $cctvExit,
                'stdout_len'  => strlen($cctvRaw),
                'stdout_head' => substr($cctvRaw, 0, 200),
                'elapsed_s'   => $elapsed ?? null,
                'stderr'      => $cctvStderr,
                'script'      => $cctvScript,
            ]);

            $cctvStart = strpos($cctvRaw, '{');
            $cctvEnd   = strrpos($cctvRaw, '}');
            if ($cctvStart !== false && $cctvEnd !== false && $cctvEnd > $cctvStart) {
                $cctvJson    = substr($cctvRaw, $cctvStart, $cctvEnd - $cctvStart + 1);
                $cctvResult  = json_decode($cctvJson, true);
                $snapshotB64 = $cctvResult['snapshot_b64'] ?? '';
                if (! empty($snapshotB64)) {
                    $imgData = base64_decode($snapshotB64, true);
                    if ($imgData !== false && strlen($imgData) > 500) {
                        $filename = 'snapshots/' . strtolower($uid) . '_' . now()->format('Ymd_His') . '.jpg';
                        $saved    = \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $imgData);
                        if ($saved) {
                            $gambarPath = $filename;
                        } else {
                            \Illuminate\Support\Facades\Log::warning('Storage::put gagal untuk snapshot: ' . $filename);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('CCTV capture exception: ' . $e->getMessage());
        }

        // Catat log akses
        // -----------------------------------------------------------------------
        // LOGIKA KEHADIRAN / PRESENCE
        // Aturan bisnis:
        //   1. Gate (gerbang): toggle masuk/keluar. Di luar → masuk; di dalam → keluar.
        //   2. Room: hanya bisa masuk jika sudah di dalam area (current_location ≠ null,
        //            tapi bukan room lain). Keluar ruangan → kembali ke level gate.
        //   3. Tidak bisa masuk ruangan langsung tanpa lewat gerbang.
        //   4. Tidak bisa masuk ruangan A jika sedang berada di ruangan B.
        // -----------------------------------------------------------------------
        $direction = null;
        $presenceDenied = false;
        $presencePesan  = '';

        if ($match) {
            $location = Location::find($locationId);
            $presence = KadetPresence::with('currentLocation')
                ->firstOrNew(['kadet_id' => $kadet->id]);
            $gateLocation = Location::where('location_type', 'gate')->where('is_active', true)->first();

            if ($location && $location->location_type === 'gate') {
                // --- GERBANG ---
                if ($presence->current_location_id === null) {
                    // Kadet di luar → MASUK
                    $direction = 'masuk';
                    $presence->current_location_id = $locationId;
                    $presence->entered_at = now();
                    $presencePesan = "Kadet {$kadet->nama_lengkap} masuk ke area UNHAN.";
                    $presence->save();
                } elseif ($presence->current_location_id === $gateLocation?->id) {
                    // Kadet sudah di dalam area (level gerbang, belum di ruangan) → KELUAR
                    $direction = 'keluar';
                    $presence->current_location_id = null;
                    $presence->entered_at = null;
                    $presencePesan = "Kadet {$kadet->nama_lengkap} keluar dari area UNHAN.";
                    $presence->save();
                } else {
                    // Kadet sedang berada di dalam ruangan → TOLAK, harus keluar ruangan dulu
                    $ruanganSaat = $presence->currentLocation?->nama_lokasi ?? 'ruangan';
                    $presenceDenied = true;
                    $status = 'denied';
                    $keterangan = "Akses ditolak: kadet {$kadet->nama_lengkap} masih berada di {$ruanganSaat}. Scan keluar dari {$ruanganSaat} terlebih dahulu.";
                    $presencePesan = $keterangan;
                }

            } elseif ($location && $location->location_type === 'room') {
                // --- RUANGAN ---
                if ($presence->current_location_id === null) {
                    // Belum masuk gerbang sama sekali → TOLAK
                    $presenceDenied = true;
                    $status = 'denied';
                    $keterangan = "Akses ditolak: kadet {$kadet->nama_lengkap} belum melewati gerbang ksatrian.";
                    $presencePesan = $keterangan;
                } elseif ($presence->current_location_id === $locationId) {
                    // Sudah di ruangan ini → KELUAR (scan keluar)
                    $direction = 'keluar';
                    // Kembalikan ke level gerbang
                    $presence->current_location_id = $gateLocation?->id;
                    $presence->entered_at = $presence->entered_at; // tetap
                    $presence->save();
                    $presencePesan = "Kadet {$kadet->nama_lengkap} keluar dari {$location->nama_lokasi}.";
                } elseif ($gateLocation && $presence->current_location_id === $gateLocation->id) {
                    // Ada di area gerbang (belum di ruangan) → MASUK ruangan
                    $direction = 'masuk';
                    $presence->current_location_id = $locationId;
                    $presence->save();
                    $presencePesan = "Kadet {$kadet->nama_lengkap} masuk ke {$location->nama_lokasi}.";
                } else {
                    // Sedang di ruangan lain → TOLAK
                    $ruanganLain = $presence->currentLocation?->nama_lokasi ?? 'ruangan lain';
                    $presenceDenied = true;
                    $status = 'denied';
                    $keterangan = "Akses ditolak: kadet {$kadet->nama_lengkap} sedang berada di {$ruanganLain}. Keluar dulu sebelum masuk {$location->nama_lokasi}.";
                    $presencePesan = $keterangan;
                }
            }
        }

        AccessLog::create([
            'kadet_id'    => $kadet->id,
            'location_id' => $locationId,
            'rfid_uid'    => $uid,
            'waktu_akses' => now(),
            'status'      => $status,
            'direction'   => $direction,
            'keterangan'  => $presencePesan ?: $keterangan,
            'gambar_path' => $gambarPath,
        ]);

        $fotoUrl = $kadet->foto_path
            ? asset('storage/' . $kadet->foto_path)
            : 'https://ui-avatars.com/api/?name=' . urlencode($kadet->nama_lengkap) . '&size=80&background=1a1a2e&color=e94560';

        // Ambil data presence terbaru untuk dikembalikan ke frontend
        $presenceFresh = KadetPresence::with('currentLocation')->where('kadet_id', $kadet->id)->first();

        return response()->json([
            'success'      => true,
            'status'       => $status,
            'match'        => $match,
            'confidence'   => $confidence,
            'keterangan'   => $presencePesan ?: $keterangan,
            'direction'    => $direction,
            'snapshot_url' => $gambarPath ? asset('storage/' . $gambarPath) : null,
            'presence'     => [
                'is_inside'       => $presenceFresh && $presenceFresh->current_location_id !== null,
                'lokasi_sekarang' => $presenceFresh?->currentLocation?->nama_lokasi,
            ],
            'kadet'        => [
                'nama_lengkap' => $kadet->nama_lengkap,
                'nim'          => $kadet->nim,
                'prodi'        => $kadet->prodi,
                'foto_url'     => $fotoUrl,
            ],
        ]);
    }

    /**
     * Frontend memanggil ini saat tombol MULAI SCAN ditekan.
     */
    public function start(Request $request): JsonResponse
    {
        $request->validate([
            'location_id' => ['required', 'exists:locations,id'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Worker Python sudah berjalan. Monitoring log di bawah.',
        ]);
    }

    /**
     * Endpoint AJAX — ambil log terbaru di pos ini.
     * Dipanggil setiap beberapa detik oleh frontend Bootstrap.
     *
     * Query param: ?after_id=123 untuk ambil hanya log setelah ID tertentu
     */
    public function latest(Request $request): JsonResponse
    {
        $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $user       = Auth::user();
        $locationId = $user->location_id;
        $afterId    = $request->integer('after_id', 0);

        $logs = AccessLog::with(['kadet:id,nama_lengkap,nim,foto_path', 'location:id,nama_lokasi,kode_lokasi'])
            ->when($locationId, fn ($q) => $q->where('location_id', $locationId))
            ->when($afterId > 0, fn ($q) => $q->where('id', '>', $afterId))
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(fn ($log) => [
                'id'           => $log->id,
                'rfid_uid'     => $log->rfid_uid,
                'status'       => $log->status,
                'direction'    => $log->direction,
                'waktu_akses'  => $log->waktu_akses?->format('d/m/Y H:i:s'),
                'keterangan'   => $log->keterangan,
                'gambar_url'   => $log->gambar_path
                    ? asset('storage/' . $log->gambar_path)
                    : null,
                'kadet'        => $log->kadet
                    ? [
                        'nama_lengkap' => $log->kadet->nama_lengkap,
                        'nim'          => $log->kadet->nim,
                        'foto_url'     => $log->kadet->foto_path
                            ? asset('storage/' . $log->kadet->foto_path)
                            : null,
                    ]
                    : null,
                'lokasi'       => $log->location?->nama_lokasi,
            ]);

        return response()->json([
            'success' => true,
            'data'    => $logs,
        ]);
    }
}
