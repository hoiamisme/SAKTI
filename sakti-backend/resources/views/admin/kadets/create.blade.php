{{--
    File   : resources/views/admin/kadets/create.blade.php
    Fungsi : Form tambah kadet baru + capture wajah dari webcam laptop
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Tambah Kadet')
@section('page-title', 'Tambah Kadet Baru')

@section('content')

<div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.kadets.index') }}" class="btn btn-sm btn-outline-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Tambah Kadet Baru</h5>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-8">
        <form action="{{ route('admin.kadets.store') }}" method="POST" enctype="multipart/form-data" id="formKadet">
            @csrf
            {{-- Hidden field: base64 wajah dari webcam --}}
            <input type="hidden" name="face_image" id="faceImageInput">

            {{-- Identitas --}}
            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-person-badge me-2 text-muted"></i>Identitas Kadet
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama_lengkap"
                                   class="form-control @error('nama_lengkap') is-invalid @enderror"
                                   value="{{ old('nama_lengkap') }}"
                                   placeholder="Lettu Inf. Budi Santoso">
                            @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                NIM / Nomor Kadet <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nim"
                                   class="form-control @error('nim') is-invalid @enderror"
                                   value="{{ old('nim') }}" placeholder="202400001">
                            @error('nim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Angkatan <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="angkatan"
                                   class="form-control @error('angkatan') is-invalid @enderror"
                                   value="{{ old('angkatan', date('Y')) }}" min="2000" max="2100">
                            @error('angkatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Program Studi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="prodi"
                                   class="form-control @error('prodi') is-invalid @enderror"
                                   value="{{ old('prodi') }}"
                                   placeholder="Infanteri / Teknik Militer / ...">
                            @error('prodi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="aktif"    {{ old('status','aktif') === 'aktif'    ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ old('status') === 'nonaktif' ? 'selected' : '' }}>Non-aktif</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                RFID UID <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="rfid_uid"
                                   class="form-control font-monospace @error('rfid_uid') is-invalid @enderror"
                                   value="{{ old('rfid_uid') }}" placeholder="A1B2C3D4">
                            @error('rfid_uid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Foto Wajah (Webcam + Upload) --}}
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-camera-video me-2 text-muted"></i>Foto Wajah untuk Face Recognition</span>
                    <span id="faceStatusBadge" class="badge bg-secondary">Belum direkam</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        {{-- Panel Webcam --}}
                        <div class="col-12 col-md-7">
                            <div class="position-relative rounded overflow-hidden border bg-black"
                                 style="aspect-ratio:4/3;max-height:300px;">
                                <video id="webcamVideo" autoplay playsinline muted
                                       class="w-100 h-100 object-fit-cover"
                                       style="display:none;transform:scaleX(-1)"></video>
                                <canvas id="webcamCanvas" style="display:none"></canvas>
                                <div id="webcamPlaceholder"
                                     class="d-flex flex-column align-items-center justify-content-center h-100 text-muted"
                                     style="min-height:200px">
                                    <i class="bi bi-camera-video-off" style="font-size:2.5rem"></i>
                                    <small class="mt-2">Kamera belum aktif</small>
                                </div>
                                {{-- Overlay countdown --}}
                                <div id="countdownOverlay"
                                     class="position-absolute top-50 start-50 translate-middle
                                            rounded-circle d-flex align-items-center justify-content-center"
                                     style="display:none!important;width:80px;height:80px;
                                            background:rgba(233,69,96,.85);font-size:2rem;
                                            color:#fff;font-weight:700"></div>
                            </div>

                            <div class="d-flex gap-2 mt-2">
                                <button type="button" id="btnStartCam" class="btn btn-sm btn-outline-primary flex-fill">
                                    <i class="bi bi-camera-video me-1"></i>Aktifkan Kamera
                                </button>
                                <button type="button" id="btnCapture" class="btn btn-sm flex-fill"
                                        style="background:#e94560;color:#fff;border:none;" disabled>
                                    <i class="bi bi-camera me-1"></i>Ambil Foto
                                </button>
                                <button type="button" id="btnRetake" class="btn btn-sm btn-outline-secondary flex-fill"
                                        style="display:none">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i>Ulangi
                                </button>
                            </div>
                        </div>

                        {{-- Preview + Upload Manual --}}
                        <div class="col-12 col-md-5 d-flex flex-column gap-3">
                            {{-- Hasil capture --}}
                            <div>
                                <p class="form-label fw-semibold mb-1" style="font-size:.82rem;">
                                    Hasil Capture Wajah
                                </p>
                                <img id="capturePreview"
                                     src="https://ui-avatars.com/api/?name=Kadet&size=160&background=1a1a2e&color=e94560"
                                     alt="Hasil capture" class="rounded border w-100 object-fit-cover"
                                     style="max-height:160px;object-fit:cover">
                                <div id="captureInfo" class="form-text text-success mt-1" style="display:none">
                                    <i class="bi bi-check-circle-fill me-1"></i>Foto wajah berhasil direkam.
                                </div>
                            </div>

                            {{-- Upload manual (fallback) --}}
                            <div>
                                <label class="form-label fw-semibold" style="font-size:.82rem;">
                                    Atau Upload Foto Wajah
                                </label>
                                <input type="file" name="foto" id="foto"
                                       class="form-control form-control-sm @error('foto') is-invalid @enderror"
                                       accept="image/jpeg,image/png">
                                <div class="form-text">JPG/PNG, maks 2 MB. Digunakan jika tidak capture webcam.</div>
                                @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 mt-3 mb-0" style="font-size:.82rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Foto dari webcam akan disimpan ke database dan digunakan oleh sistem
                        <strong>ArcFace</strong> untuk verifikasi wajah saat kadet tap RFID.
                        Pastikan wajah kadet terlihat jelas, pencahayaan cukup.
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.kadets.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn" style="background:#e94560;color:#fff;border:none;">
                    <i class="bi bi-save me-1"></i>Simpan Kadet
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    let stream = null;
    const video       = document.getElementById('webcamVideo');
    const canvas      = document.getElementById('webcamCanvas');
    const placeholder = document.getElementById('webcamPlaceholder');
    const btnStart    = document.getElementById('btnStartCam');
    const btnCapture  = document.getElementById('btnCapture');
    const btnRetake   = document.getElementById('btnRetake');
    const preview     = document.getElementById('capturePreview');
    const captureInfo = document.getElementById('captureInfo');
    const faceInput   = document.getElementById('faceImageInput');
    const badge       = document.getElementById('faceStatusBadge');
    const countdown   = document.getElementById('countdownOverlay');

    // Upload manual → preview
    document.getElementById('foto').addEventListener('change', function () {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => { preview.src = e.target.result; };
            reader.readAsDataURL(file);
        }
    });

    // Aktifkan kamera
    btnStart.addEventListener('click', async () => {
        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 640, height: 480 } });
            video.srcObject = stream;
            video.style.display = 'block';
            placeholder.style.display = 'none';
            btnStart.disabled = true;
            btnCapture.disabled = false;
            badge.textContent = 'Kamera aktif';
            badge.className = 'badge bg-primary';
        } catch (err) {
            alert('Tidak dapat mengakses kamera: ' + err.message);
        }
    });

    // Ambil foto dengan countdown 3-2-1
    btnCapture.addEventListener('click', () => {
        let count = 3;
        countdown.style.display = 'flex';
        countdown.textContent = count;
        btnCapture.disabled = true;

        const timer = setInterval(() => {
            count--;
            if (count > 0) {
                countdown.textContent = count;
            } else {
                clearInterval(timer);
                countdown.style.display = 'none';
                doCapture();
            }
        }, 1000);
    });

    function doCapture() {
        canvas.width  = video.videoWidth  || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        // Mirror karena video di-flip CSS
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
        preview.src = dataUrl;

        // Simpan base64 ke hidden input (tanpa header)
        faceInput.value = dataUrl.replace(/^data:image\/jpeg;base64,/, '');

        // Update UI
        captureInfo.style.display = 'block';
        badge.textContent = '✓ Wajah terekam';
        badge.className = 'badge bg-success';
        btnCapture.style.display = 'none';
        btnRetake.style.display = '';

        // Hentikan kamera
        stopCamera();
    }

    // Ulangi capture
    btnRetake.addEventListener('click', async () => {
        faceInput.value = '';
        captureInfo.style.display = 'none';
        badge.textContent = 'Kamera aktif';
        badge.className = 'badge bg-primary';
        btnRetake.style.display = 'none';
        btnCapture.style.display = '';
        btnCapture.disabled = false;
        btnStart.disabled = true;

        try {
            stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width: 640, height: 480 } });
            video.srcObject = stream;
            video.style.display = 'block';
            placeholder.style.display = 'none';
        } catch (err) {
            alert('Tidak dapat mengakses kamera: ' + err.message);
        }
    });

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(t => t.stop());
            stream = null;
        }
        video.style.display = 'none';
    }

    // Pastikan kamera dimatikan saat navigasi pergi
    window.addEventListener('beforeunload', stopCamera);
})();
</script>
@endpush
