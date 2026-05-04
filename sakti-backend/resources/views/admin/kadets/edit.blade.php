{{--
    File   : resources/views/admin/kadets/edit.blade.php
    Fungsi : Form edit data kadet + capture ulang wajah dari webcam
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Edit Kadet')
@section('page-title', 'Edit Kadet')

@section('content')

<div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.kadets.index') }}" class="btn btn-sm btn-outline-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Edit: {{ $kadet->nama_lengkap }}</h5>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-8">
        <form action="{{ route('admin.kadets.update', $kadet) }}" method="POST" enctype="multipart/form-data" id="formKadet">
            @csrf
            @method('PUT')
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
                                   value="{{ old('nama_lengkap', $kadet->nama_lengkap) }}">
                            @error('nama_lengkap')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                NIM / Nomor Kadet <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nim"
                                   class="form-control @error('nim') is-invalid @enderror"
                                   value="{{ old('nim', $kadet->nim) }}">
                            @error('nim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Angkatan <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="angkatan"
                                   class="form-control @error('angkatan') is-invalid @enderror"
                                   value="{{ old('angkatan', $kadet->angkatan) }}" min="2000" max="2100">
                            @error('angkatan')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Program Studi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="prodi"
                                   class="form-control @error('prodi') is-invalid @enderror"
                                   value="{{ old('prodi', $kadet->prodi) }}">
                            @error('prodi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="aktif"    {{ old('status', $kadet->status) === 'aktif'    ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ old('status', $kadet->status) === 'nonaktif' ? 'selected' : '' }}>Non-aktif</option>
                            </select>
                            @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                RFID UID <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="rfid_uid"
                                   class="form-control font-monospace @error('rfid_uid') is-invalid @enderror"
                                   value="{{ old('rfid_uid', $kadet->rfid_uid) }}">
                            @error('rfid_uid')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Foto Wajah --}}
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-camera-video me-2 text-muted"></i>Foto Wajah untuk Face Recognition</span>
                    <span id="faceStatusBadge" class="badge {{ $kadet->face_image ? 'bg-success' : 'bg-secondary' }}">
                        {{ $kadet->face_image ? '✓ Sudah direkam' : 'Belum direkam' }}
                    </span>
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
                                    <small class="mt-2">Klik "Aktifkan Kamera" untuk merekam ulang</small>
                                </div>
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

                        {{-- Preview --}}
                        <div class="col-12 col-md-5 d-flex flex-column gap-3">
                            <div>
                                <p class="form-label fw-semibold mb-1" style="font-size:.82rem;">
                                    Foto Wajah Tersimpan
                                </p>
                                <img id="capturePreview"
                                     src="{{ $kadet->face_image
                                            ? 'data:image/jpeg;base64,'.$kadet->face_image
                                            : ($kadet->foto_path
                                                ? asset('storage/'.$kadet->foto_path)
                                                : 'https://ui-avatars.com/api/?name='.urlencode($kadet->nama_lengkap).'&size=160&background=1a1a2e&color=e94560') }}"
                                     alt="Foto wajah" class="rounded border w-100 object-fit-cover"
                                     style="max-height:160px;object-fit:cover">
                                <div id="captureInfo" class="form-text text-success mt-1" style="display:none">
                                    <i class="bi bi-check-circle-fill me-1"></i>Foto baru berhasil direkam.
                                </div>
                            </div>

                            <div>
                                <label class="form-label fw-semibold" style="font-size:.82rem;">
                                    Atau Upload Foto Wajah Baru
                                </label>
                                <input type="file" name="foto" id="foto"
                                       class="form-control form-control-sm @error('foto') is-invalid @enderror"
                                       accept="image/jpeg,image/png">
                                <div class="form-text">JPG/PNG, maks 2 MB. Kosongkan jika tidak ingin mengganti.</div>
                                @error('foto')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 px-3 mt-3 mb-0" style="font-size:.82rem;">
                        <i class="bi bi-info-circle me-1"></i>
                        Merekam ulang foto akan <strong>mengganti</strong> foto wajah lama di database.
                        Sistem ArcFace akan menggunakan foto baru pada sesi scan berikutnya.
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.kadets.show', $kadet) }}" class="btn btn-outline-secondary">Batal</a>
                <button type="submit" class="btn" style="background:#e94560;color:#fff;border:none;">
                    <i class="bi bi-save me-1"></i>Simpan Perubahan
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

    document.getElementById('foto').addEventListener('change', function () {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => { preview.src = e.target.result; };
            reader.readAsDataURL(file);
        }
    });

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
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0);

        const dataUrl = canvas.toDataURL('image/jpeg', 0.92);
        preview.src = dataUrl;
        faceInput.value = dataUrl.replace(/^data:image\/jpeg;base64,/, '');

        captureInfo.style.display = 'block';
        badge.textContent = '✓ Foto baru direkam';
        badge.className = 'badge bg-success';
        btnCapture.style.display = 'none';
        btnRetake.style.display = '';
        stopCamera();
    }

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
        if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
        video.style.display = 'none';
    }

    window.addEventListener('beforeunload', stopCamera);
})();
</script>
@endpush

@extends('layouts.admin')

@section('title', 'Edit Kadet')
@section('page-title', 'Edit Kadet')

@section('content')

<div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.kadets.index') }}" class="btn btn-sm btn-outline-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Edit: {{ $kadet->nama_lengkap }}</h5>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <form action="{{ route('admin.kadets.update', $kadet) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

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
                                   value="{{ old('nama_lengkap', $kadet->nama_lengkap) }}">
                            @error('nama_lengkap')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                NIM / Nomor Kadet <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nim"
                                   class="form-control @error('nim') is-invalid @enderror"
                                   value="{{ old('nim', $kadet->nim) }}">
                            @error('nim')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Angkatan <span class="text-danger">*</span>
                            </label>
                            <input type="number" name="angkatan"
                                   class="form-control @error('angkatan') is-invalid @enderror"
                                   value="{{ old('angkatan', $kadet->angkatan) }}"
                                   min="2000" max="2100">
                            @error('angkatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Program Studi <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="prodi"
                                   class="form-control @error('prodi') is-invalid @enderror"
                                   value="{{ old('prodi', $kadet->prodi) }}">
                            @error('prodi')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select name="status"
                                    class="form-select @error('status') is-invalid @enderror">
                                <option value="aktif"    {{ old('status', $kadet->status) === 'aktif'    ? 'selected' : '' }}>Aktif</option>
                                <option value="nonaktif" {{ old('status', $kadet->status) === 'nonaktif' ? 'selected' : '' }}>Non-aktif</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                RFID UID <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="rfid_uid"
                                   class="form-control font-monospace @error('rfid_uid') is-invalid @enderror"
                                   value="{{ old('rfid_uid', $kadet->rfid_uid) }}">
                            @error('rfid_uid')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <i class="bi bi-camera me-2 text-muted"></i>Foto Kadet
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start gap-3">
                        <div class="flex-shrink-0">
                            <img id="fotoPreview"
                                 src="{{ $kadet->foto_path
                                        ? asset('storage/'.$kadet->foto_path)
                                        : 'https://ui-avatars.com/api/?name='.urlencode($kadet->nama_lengkap).'&size=80&background=e94560&color=fff' }}"
                                 alt="Foto kadet" width="80" height="80"
                                 class="rounded object-fit-cover border">
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label fw-semibold" style="font-size:.85rem;">
                                Ganti Foto (kosongkan jika tidak ingin mengganti)
                            </label>
                            <input type="file" name="foto" id="foto"
                                   class="form-control form-control-sm @error('foto') is-invalid @enderror"
                                   accept="image/jpeg,image/png">
                            <div class="form-text">Format: JPG/PNG. Maks 2 MB.</div>
                            @error('foto')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex gap-2 justify-content-end">
                <a href="{{ route('admin.kadets.show', $kadet) }}" class="btn btn-outline-secondary">
                    Batal
                </a>
                <button type="submit" class="btn"
                        style="background:#e94560;color:#fff;border:none;">
                    <i class="bi bi-save me-1"></i>Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.getElementById('foto').addEventListener('change', function () {
        const file = this.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => { document.getElementById('fotoPreview').src = e.target.result; };
            reader.readAsDataURL(file);
        }
    });
</script>
@endpush
