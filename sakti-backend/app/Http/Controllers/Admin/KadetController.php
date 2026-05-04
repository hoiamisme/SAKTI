<?php
// =============================================================================
// File   : app/Http\Controllers\Admin\KadetController.php
// Fungsi : CRUD manajemen data kadet oleh admin
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Kadet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class KadetController extends Controller
{
    public function index(): View
    {
        $kadets = Kadet::orderByDesc('created_at')->paginate(15);

        return view('admin.kadets.index', compact('kadets'));
    }

    public function create(): View
    {
        return view('admin.kadets.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nim'          => ['required', 'string', 'max:20', 'unique:kadets,nim'],
            'prodi'        => ['required', 'string', 'max:100'],
            'angkatan'     => ['required', 'integer', 'min:2000', 'max:2100'],
            'rfid_uid'     => ['required', 'string', 'max:50', 'unique:kadets,rfid_uid'],
            'foto'         => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'face_image'   => ['nullable', 'string'],   // base64 dari webcam
            'status'       => ['required', 'in:aktif,nonaktif'],
        ]);

        $fotoPath = null;
        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $fotoPath = $request->file('foto')->store('kadet/foto', 'public');
        }

        // Jika foto diambil dari webcam (base64), simpan juga sebagai foto_path
        $faceImage = null;
        if (!empty($validated['face_image'])) {
            $faceImage = $this->processBase64FaceImage(
                $validated['face_image'],
                $validated['rfid_uid'],
                $fotoPath,
            );
            // Gunakan hasil webcam sebagai foto tampilan jika belum ada upload manual
            if ($faceImage['foto_path'] && !$fotoPath) {
                $fotoPath = $faceImage['foto_path'];
            }
        }

        Kadet::create([
            'nama_lengkap' => $validated['nama_lengkap'],
            'nim'          => $validated['nim'],
            'prodi'        => $validated['prodi'],
            'angkatan'     => $validated['angkatan'],
            'rfid_uid'     => $validated['rfid_uid'],
            'status'       => $validated['status'],
            'foto_path'    => $fotoPath,
            'face_image'   => $faceImage['base64'] ?? null,
        ]);

        return redirect()->route('admin.kadets.index')
            ->with('success', 'Data kadet berhasil ditambahkan.');
    }

    public function show(Kadet $kadet): View
    {
        $kadet->load(['accessLogs' => fn ($q) => $q->latest()->limit(10), 'accessPermissions.location']);

        return view('admin.kadets.show', compact('kadet'));
    }

    public function edit(Kadet $kadet): View
    {
        return view('admin.kadets.edit', compact('kadet'));
    }

    public function update(Request $request, Kadet $kadet): RedirectResponse
    {
        $validated = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'nim'          => ['required', 'string', 'max:20', 'unique:kadets,nim,' . $kadet->id],
            'prodi'        => ['required', 'string', 'max:100'],
            'angkatan'     => ['required', 'integer', 'min:2000', 'max:2100'],
            'rfid_uid'     => ['required', 'string', 'max:50', 'unique:kadets,rfid_uid,' . $kadet->id],
            'foto'         => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'face_image'   => ['nullable', 'string'],   // base64 dari webcam
            'status'       => ['required', 'in:aktif,nonaktif'],
        ]);

        $updates = [
            'nama_lengkap' => $validated['nama_lengkap'],
            'nim'          => $validated['nim'],
            'prodi'        => $validated['prodi'],
            'angkatan'     => $validated['angkatan'],
            'rfid_uid'     => $validated['rfid_uid'],
            'status'       => $validated['status'],
        ];

        if ($request->hasFile('foto') && $request->file('foto')->isValid()) {
            $updates['foto_path'] = $request->file('foto')->store('kadet/foto', 'public');
        }

        if (!empty($validated['face_image'])) {
            $faceImage = $this->processBase64FaceImage(
                $validated['face_image'],
                $validated['rfid_uid'],
                $kadet->foto_path,
            );
            $updates['face_image'] = $faceImage['base64'];
            if ($faceImage['foto_path'] && !isset($updates['foto_path'])) {
                $updates['foto_path'] = $faceImage['foto_path'];
            }
        }

        $kadet->update($updates);

        return redirect()->route('admin.kadets.index')
            ->with('success', 'Data kadet berhasil diperbarui.');
    }

    public function destroy(Kadet $kadet): RedirectResponse
    {
        $kadet->delete();

        return redirect()->route('admin.kadets.index')
            ->with('success', 'Data kadet berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // API: ambil face_image untuk Python worker (dipanggil via API key)
    // -------------------------------------------------------------------------

    public function faceImage(Kadet $kadet): JsonResponse
    {
        if (!$kadet->face_image) {
            return response()->json(['success' => false, 'message' => 'Foto wajah belum direkam.'], 404);
        }

        return response()->json([
            'success'    => true,
            'rfid_uid'   => $kadet->rfid_uid,
            'face_image' => $kadet->face_image,   // base64 JPEG
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helper
    // -------------------------------------------------------------------------

    /**
     * Proses base64 face image: strip header, simpan sebagai JPG di storage,
     * kembalikan base64 bersih dan foto_path.
     */
    private function processBase64FaceImage(string $base64, string $rfidUid, ?string $existingFotoPath): array
    {
        // Hapus header data URI jika ada: "data:image/jpeg;base64,..."
        $clean = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $imageData = base64_decode($clean, true);

        if ($imageData === false || strlen($imageData) < 100) {
            return ['base64' => null, 'foto_path' => null];
        }

        // Simpan sebagai foto tampilan
        $filename = 'kadet/foto/face_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $rfidUid) . '_' . time() . '.jpg';
        Storage::disk('public')->put($filename, $imageData);

        return [
            'base64'    => $clean,
            'foto_path' => $filename,
        ];
    }
}
