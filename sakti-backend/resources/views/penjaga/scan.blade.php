{{--
    File   : resources/views/penjaga/scan.blade.php
    Fungsi : Monitoring scan RFID real-time + polling log terbaru setiap 3 detik
             Card hasil: foto snapshot, nama kadet, status badge, detail
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.penjaga')

@section('title', 'Monitor SCAN RFID')
@section('page-title', 'Monitor SCAN RFID')

@push('styles')
<style>
    .scan-status-box {
        border-radius: 12px;
        padding: 2rem;
        text-align: center;
        min-height: 140px;
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        transition: background .3s;
    }
    .scan-status-box.idle     { background: #f8f9fa; color: #6c757d; }
    .scan-status-box.scanning { background: #e8f4fd; color: #0d6efd; }
    .scan-status-box.granted  { background: #d1e7dd; color: #0a3622; }
    .scan-status-box.denied   { background: #f8d7da; color: #58151c; }
    .scan-status-box.warning  { background: #fff3cd; color: #664d03; }

    .scan-pulse { animation: pulse 1.5s infinite; }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: .4; }
    }

    .log-card-enter {
        animation: slideIn .4s ease;
    }
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(-10px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .result-card img { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; }
    .result-card .result-name { font-size: .95rem; font-weight: 600; }
    .result-card .result-detail { font-size: .78rem; color: #6c757d; }
</style>
@endpush

@section('content')

<div class="row g-3">
    {{-- Left: control panel --}}
    <div class="col-12 col-lg-4">
        {{-- Pilih Lokasi --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-geo-alt me-2 text-muted"></i>Pilih Pos</div>
            <div class="card-body">
                <select id="locationSelect" class="form-select">
                    @if(!$locationId)
                        <option value="">-- Pilih Lokasi --</option>
                    @endif
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}"
                            {{ $locationId == $loc->id ? 'selected' : '' }}>
                            {{ $loc->nama_lokasi }} ({{ $loc->kode_lokasi }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Scan control --}}
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-toggles me-2 text-muted"></i>Kontrol Scan</div>
            <div class="card-body d-grid gap-2">
                <button id="btnStartScan" class="btn"
                        style="background:#e94560;color:#fff;border:none;">
                    <i class="bi bi-play-fill me-2"></i>MULAI SCAN
                </button>
                <button id="btnStopScan" class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-stop-fill me-2"></i>STOP
                </button>
            </div>
        </div>

        {{-- Input bar RFID (auto-fokus saat scanning aktif) --}}
        <div class="card mb-3" id="rfidInputCard" style="display:none;">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-upc-scan text-muted"></i>
                <span>Scan Kartu RFID</span>
                <span class="badge bg-success ms-auto scan-pulse" style="font-size:.7rem;">MENDENGARKAN</span>
            </div>
            <div class="card-body">
                <div class="input-group">
                    <span class="input-group-text bg-transparent">
                        <i class="bi bi-credit-card-2-front text-muted"></i>
                    </span>
                    <input type="text" id="rfidInput"
                           class="form-control font-monospace text-uppercase"
                           placeholder="Tap kartu atau ketik UID..."
                           autocomplete="off" spellcheck="false"
                           maxlength="50" style="letter-spacing:.1em;">
                    <button class="btn btn-outline-secondary" id="btnManualLookup" type="button"
                            title="Cari manual">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <div class="form-text mt-1">
                    <i class="bi bi-info-circle me-1"></i>
                    Input otomatis saat tap kartu. Tekan <kbd>Enter</kbd> untuk cari manual.
                </div>
            </div>
        </div>

        {{-- Live status box --}}
        <div id="statusBox" class="scan-status-box idle mb-3">
            <i class="bi bi-upc-scan fs-1 mb-2"></i>
            <div id="statusText" class="fw-semibold">Tekan MULAI SCAN</div>
            <div id="statusSub" class="mt-1" style="font-size:.8rem;">Pilih lokasi lalu klik tombol di atas</div>
        </div>

        {{-- Last result card --}}
        <div class="card" id="resultCard" style="display:none;">
            <div class="card-header"><i class="bi bi-person-check me-2 text-muted"></i>Hasil Terakhir</div>
            <div class="card-body result-card d-flex align-items-center gap-3">
                <img id="resultFoto" src="https://ui-avatars.com/api/?name=?&background=e94560&color=fff"
                     alt="Foto kadet">
                <div class="flex-grow-1">
                    <div class="result-name" id="resultName">&mdash;</div>
                    <div class="result-detail" id="resultNim">&mdash;</div>
                    <div class="result-detail" id="resultWaktu">&mdash;</div>
                    <div class="mt-1" id="resultBadge"></div>
                </div>
                <div class="flex-shrink-0" id="resultSnapshot" style="display:none;">
                    <img id="resultSnapshotImg" src="" alt="Snapshot" width="60" height="60"
                         class="rounded border object-fit-cover"
                         style="cursor:pointer;"
                         data-bs-toggle="modal" data-bs-target="#imgModal">
                </div>
            </div>
        </div>
    </div>

    {{-- Right: live log table --}}
    <div class="col-12 col-lg-8">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-activity me-2 text-muted"></i>Log Real-time</span>
                <div class="d-flex align-items-center gap-2">
                    <span id="scanIndicator" class="badge bg-secondary" style="font-size:.7rem;">IDLE</span>
                    <span id="pollInterval" class="text-muted" style="font-size:.75rem;"></span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height:500px;overflow-y:auto;" id="logScroll">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="ps-3">Waktu</th>
                                <th>Kadet</th>
                                <th>Status</th>
                                <th>Snapshot</th>
                                <th class="pe-3">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody id="logTableBody">
                            <tr id="noLogRow">
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bi bi-wifi-off fs-2 d-block mb-2"></i>
                                    Mulai scan untuk melihat log real-time
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top d-flex justify-content-between"
                 style="font-size:.78rem;">
                <span class="text-muted">Total ditampilkan: <strong id="logCount">0</strong></span>
                <button class="btn btn-sm btn-outline-secondary" id="btnClearLog">
                    <i class="bi bi-trash me-1"></i>Bersihkan
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Snapshot Modal --}}
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header py-2">
                <small class="text-muted" id="imgModalInfo"></small>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <img id="imgModalSrc" src="" alt="Snapshot" class="img-fluid w-100"
                     style="border-radius:0 0 .375rem .375rem;">
            </div>
        </div>
    </div>
</div>

{{-- Face Verification Modal --}}
<div class="modal fade" id="faceModal" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="faceModalLabel">
    <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
        <div class="modal-content border-0 overflow-hidden">
            {{-- Header --}}
            <div class="modal-header py-2 px-3" style="background:#1a1a2e;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-camera-video text-white"></i>
                    <span class="fw-semibold text-white" id="faceModalLabel" style="font-size:.92rem;">
                        Verifikasi Wajah
                    </span>
                </div>
                <span id="faceKadetBadge" class="badge bg-secondary ms-auto" style="font-size:.72rem;"></span>
            </div>

            {{-- Webcam area --}}
            <div class="position-relative bg-black" style="min-height:300px;">
                <video id="faceVideo" autoplay playsinline muted
                       style="width:100%;display:block;transform:scaleX(-1);"></video>

                {{-- Countdown overlay --}}
                <div id="faceCountdownOverlay"
                     class="position-absolute top-0 start-0 w-100 h-100
                            d-flex align-items-center justify-content-center"
                     style="display:none!important;background:rgba(0,0,0,.45);pointer-events:none;">
                    <span id="faceCountdownNum"
                          style="font-size:6rem;font-weight:900;color:#fff;
                                 text-shadow:0 0 40px rgba(233,69,96,.9);line-height:1;">
                    </span>
                </div>

                {{-- Result overlay (shown setelah verifikasi) --}}
                <div id="faceResultOverlay"
                     class="position-absolute top-0 start-0 w-100 h-100
                            flex-column align-items-center justify-content-center"
                     style="display:none;pointer-events:none;">
                    <i id="faceResultIcon" class="bi fs-1 mb-2"></i>
                    <div id="faceResultText" class="fw-bold text-white fs-5 text-center px-3"></div>
                    <div id="faceConfidenceTxt" class="text-white mt-1 opacity-75" style="font-size:.85rem;"></div>
                </div>

                {{-- Face guide box --}}
                <div id="faceGuideBox"
                     class="position-absolute"
                     style="top:50%;left:50%;transform:translate(-50%,-50%);
                            width:160px;height:190px;
                            border:2px dashed rgba(233,69,96,.6);
                            border-radius:50% 50% 45% 45%;
                            pointer-events:none;">
                </div>
            </div>

            {{-- Footer --}}
            <div class="modal-footer d-flex justify-content-between align-items-center py-2 px-3"
                 style="background:#f8f9fa;">
                <div id="faceStatusMsg" class="text-muted flex-grow-1 me-2" style="font-size:.8rem;">
                    Menghidupkan kamera...
                </div>
                <div class="d-flex gap-2 flex-shrink-0">
                    <button class="btn btn-sm" id="btnFaceRetry"
                            style="display:none;background:#e94560;color:#fff;border:none;">
                        <i class="bi bi-arrow-repeat me-1"></i>Ulangi
                    </button>
                    <button class="btn btn-sm btn-primary" id="btnFaceCapture" disabled>
                        <i class="bi bi-camera me-1"></i>Ambil Foto
                    </button>
                    <button class="btn btn-sm btn-outline-secondary" id="btnFaceCancel">
                        <i class="bi bi-x me-1"></i>Batal
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const latestUrl     = '{{ route("penjaga.scan.latest") }}';
    const lookupUrl     = '{{ route("penjaga.scan.lookup") }}';
    const verifyFaceUrl = '{{ route("penjaga.scan.verify-face") }}';
    const csrfToken     = '{{ csrf_token() }}';
    const POLL_MS       = 3000;
    const INPUT_DEBOUNCE = 600;

    let isScanning   = false;
    let pollTimer    = null;
    let inputTimer   = null;
    let lastId       = 0;
    let logCount     = 0;

    // Face state
    let faceStream          = null;
    let faceCountdownTimer  = null;
    let currentFaceUid      = null;
    let currentFaceKadet    = null;
    let currentLocationId   = null;

    // ---- DOM refs ----
    const btnStart      = document.getElementById('btnStartScan');
    const btnStop       = document.getElementById('btnStopScan');
    const statusBox     = document.getElementById('statusBox');
    const statusText    = document.getElementById('statusText');
    const statusSub     = document.getElementById('statusSub');
    const indicator     = document.getElementById('scanIndicator');
    const tbody         = document.getElementById('logTableBody');
    const noLogRow      = document.getElementById('noLogRow');
    const logScroll     = document.getElementById('logScroll');
    const logCountEl    = document.getElementById('logCount');
    const rfidInputCard = document.getElementById('rfidInputCard');
    const rfidInput     = document.getElementById('rfidInput');
    const btnManual     = document.getElementById('btnManualLookup');

    // ---- Status helpers ----
    function setStatus(cls, icon, text, sub) {
        statusBox.className = `scan-status-box ${cls}`;
        statusBox.querySelector('i').className =
            `bi ${icon} fs-1 mb-2${cls === 'scanning' ? ' scan-pulse' : ''}`;
        statusText.textContent = text;
        if (statusSub) statusSub.textContent = sub || '';
    }

    function setBadge(status) {
        const map = {
            granted:       { cls: 'bg-success',           lbl: '? AKSES DIBERIKAN'   },
            denied:        { cls: 'bg-danger',            lbl: '? AKSES DITOLAK'     },
            face_mismatch: { cls: 'bg-warning text-dark', lbl: '? WAJAH TIDAK COCOK' },
            rfid_unknown:  { cls: 'bg-secondary',         lbl: '? RFID TIDAK DIKENAL'},
        };
        const cfg = map[status] || { cls: 'bg-secondary', lbl: status };
        document.getElementById('resultBadge').innerHTML =
            `<span class="badge ${cfg.cls}" style="font-size:.8rem;">${cfg.lbl}</span>`;
    }

    function getStatusBoxClass(s) {
        return { granted: 'granted', denied: 'denied',
                 face_mismatch: 'warning', rfid_unknown: 'warning',
                 pending_face: 'scanning' }[s] || 'scanning';
    }
    function getStatusIcon(s) {
        return { granted: 'bi-check-circle-fill', denied: 'bi-x-circle-fill',
                 face_mismatch: 'bi-exclamation-triangle-fill',
                 rfid_unknown:  'bi-question-circle-fill',
                 pending_face:  'bi-camera-video-fill' }[s] || 'bi-circle';
    }
    function getStatusText(s) {
        return { granted: 'AKSES DIBERIKAN', denied: 'AKSES DITOLAK',
                 face_mismatch: 'WAJAH TIDAK COCOK',
                 rfid_unknown:  'RFID TIDAK DIKENAL',
                 pending_face:  'VERIFIKASI WAJAH...' }[s] || s.toUpperCase();
    }

    function statusBadgeHtml(status, direction) {
        const dirHtml = direction
            ? `<span class="badge ${direction === 'masuk' ? 'bg-primary' : 'bg-warning text-dark'} ms-1" style="font-size:.65rem;">
                   <i class="bi ${direction === 'masuk' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right'}"></i> ${direction.toUpperCase()}
               </span>`
            : '';
        const map = {
            granted:       ['bg-success',           'Diberikan'],
            denied:        ['bg-danger',            'Ditolak'],
            face_mismatch: ['bg-warning text-dark', 'Wajah ✗'],
            rfid_unknown:  ['bg-secondary',         'RFID ✗'],
        };
        const [cls, lbl] = map[status] || ['bg-light text-dark', status];
        return `<span class="badge ${cls}" style="font-size:.7rem;">${lbl}</span>${dirHtml}`;
    }

    // ---- Tambah baris log ----
    function addLogRow(uid, status, kadet, pesan, waktu, snapshotUrl, direction) {
        if (noLogRow && noLogRow.parentNode) noLogRow.remove();
        const waktuStr = waktu || new Date().toLocaleString('id-ID', {
            day:'2-digit', month:'2-digit', year:'numeric',
            hour:'2-digit', minute:'2-digit', second:'2-digit',
        });
        const fotoUrl = kadet?.foto_url ||
            `https://ui-avatars.com/api/?name=${encodeURIComponent(kadet?.nama_lengkap||'?')}&size=36&background=e94560&color=fff`;
        const tr = document.createElement('tr');
        tr.className = 'log-card-enter';
        tr.innerHTML = `
            <td class="ps-3 text-nowrap" style="font-size:.8rem;">${waktuStr}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${fotoUrl}" width="30" height="30"
                         class="rounded-circle object-fit-cover border flex-shrink-0" alt="">
                    <div>
                        <div class="fw-semibold" style="font-size:.82rem;">
                            ${kadet?.nama_lengkap || '<em class="text-muted">Tak dikenal</em>'}
                        </div>
                        <div class="text-muted" style="font-size:.72rem;">${kadet?.nim || uid}</div>
                    </div>
                </div>
            </td>
            <td>${statusBadgeHtml(status, direction)}</td>
            <td>${snapshotUrl
                ? `<img src="${snapshotUrl}" alt="CCTV" width="36" height="36" class="rounded border object-fit-cover" style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#imgModal" data-src="${snapshotUrl}" data-info="${kadet?.nama_lengkap||uid}" onerror="this.parentElement.innerHTML='<span class=&quot;text-muted&quot;>&amp;mdash;</span>'">`
                : '<span class="text-muted">&mdash;</span>'}</td>
            <td class="pe-3 text-muted" style="font-size:.78rem;max-width:160px;">
                ${(pesan||'').substring(0,60) || '&mdash;'}
            </td>`;
        tbody.insertBefore(tr, tbody.firstChild);
        logCount++;
        logCountEl.textContent = logCount;
        while (tbody.rows.length > 50) tbody.deleteRow(tbody.rows.length - 1);
    }

    // ---- Result card ----
    function updateResultCard(uid, status, kadet, pesan, snapshotUrl, direction, presence) {
        const card = document.getElementById('resultCard');
        card.style.display = '';
        document.getElementById('resultFoto').src = kadet?.foto_url ||
            `https://ui-avatars.com/api/?name=${encodeURIComponent(kadet?.nama_lengkap||'?')}&background=e94560&color=fff`;
        document.getElementById('resultName').textContent  = kadet?.nama_lengkap || 'Tidak Dikenal';
        document.getElementById('resultNim').textContent   = kadet ? `${kadet.nim||''}  ·  ${kadet.prodi||''}` : `UID: ${uid}`;
        document.getElementById('resultWaktu').textContent = new Date().toLocaleTimeString('id-ID');
        const snapEl = document.getElementById('resultSnapshot');
        const snapImg = document.getElementById('resultSnapshotImg');
        if (snapshotUrl) {
            snapImg.src = snapshotUrl;
            snapImg.setAttribute('data-bs-toggle', 'modal');
            snapImg.setAttribute('data-bs-target', '#imgModal');
            snapImg.setAttribute('data-src', snapshotUrl);
            snapImg.setAttribute('data-info', (kadet?.nama_lengkap || uid) + ' — ' + new Date().toLocaleTimeString('id-ID'));
            snapEl.style.display = '';
        } else {
            snapEl.style.display = 'none';
        }

        // Badge status + direction
        const dirHtml = direction
            ? `<span class="badge ${direction === 'masuk' ? 'bg-primary' : 'bg-warning text-dark'} ms-1" style="font-size:.8rem;">
                   <i class="bi ${direction === 'masuk' ? 'bi-box-arrow-in-right' : 'bi-box-arrow-right'}"></i>
                   ${direction === 'masuk' ? 'MASUK' : 'KELUAR'}
               </span>`
            : '';
        // Presence info
        const presHtml = presence
            ? `<div class="mt-1 text-muted" style="font-size:.75rem;">
                   <i class="bi ${presence.is_inside ? 'bi-geo-alt-fill text-success' : 'bi-geo-alt text-secondary'}"></i>
                   ${presence.is_inside ? ('Di dalam: ' + (presence.lokasi_sekarang || 'Area UNHAN')) : 'Di luar area'}
               </div>`
            : '';

        document.getElementById('resultBadge').innerHTML =
            statusBadgeHtml(status) + dirHtml + presHtml;

        setStatus(getStatusBoxClass(status), getStatusIcon(status),
                  getStatusText(status), kadet?.nama_lengkap || uid);
    }

    // ---- Log dari polling (Python worker) ----
    function addLogRowFromPoll(log) {
        if (noLogRow && noLogRow.parentNode) noLogRow.remove();
        const tr = document.createElement('tr');
        tr.className = 'log-card-enter';
        const fotoUrl = log.kadet?.foto_url ||
            `https://ui-avatars.com/api/?name=${encodeURIComponent(log.kadet?.nama_lengkap||'?')}&size=36&background=e94560&color=fff`;
        tr.innerHTML = `
            <td class="ps-3 text-nowrap" style="font-size:.8rem;">${log.waktu_akses||'-'}</td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <img src="${fotoUrl}" width="30" height="30"
                         class="rounded-circle object-fit-cover border flex-shrink-0" alt="">
                    <div>
                        <div class="fw-semibold" style="font-size:.82rem;">
                            ${log.kadet?.nama_lengkap||'<em class="text-muted">Tak dikenal</em>'}
                        </div>
                        <div class="text-muted" style="font-size:.72rem;">${log.kadet?.nim||log.rfid_uid||''}</div>
                    </div>
                </div>
            </td>
            <td>${statusBadgeHtml(log.status, log.direction || null)}</td>
            <td>${log.gambar_url
                ? `<img src="${log.gambar_url}" alt="Snap" width="36" height="36"
                        class="rounded border object-fit-cover" style="cursor:pointer;"
                        data-bs-toggle="modal" data-bs-target="#imgModal"
                        data-src="${log.gambar_url}"
                        data-info="${log.kadet?.nama_lengkap||'?'}  |  ${log.waktu_akses||''}"
                        onerror="this.parentElement.innerHTML='<span class=&quot;text-muted&quot;>&amp;mdash;</span>'">` 
                : '<span class="text-muted">&mdash;</span>'}</td>
            <td class="pe-3 text-muted" style="font-size:.78rem;max-width:160px;">
                ${(log.keterangan||'').substring(0,60)||'&mdash;'}
            </td>`;
        tbody.insertBefore(tr, tbody.firstChild);
        logCount++;
        logCountEl.textContent = logCount;
        while (tbody.rows.length > 50) tbody.deleteRow(tbody.rows.length - 1);
    }

    function flashStatusBox() {
        statusBox.style.transform = 'scale(1.03)';
        statusBox.style.transition = 'transform .15s';
        setTimeout(() => { statusBox.style.transform = ''; }, 200);
    }

    // =========================================================================
    // RFID Input -- lookup langsung saat kartu di-tap
    // =========================================================================
    function triggerLookup() {
        const uid = rfidInput.value.trim().toUpperCase();
        if (!uid) return;

        const locId = document.getElementById('locationSelect').value;
        setStatus('scanning', 'bi-upc-scan', 'Menganalisa UID...', uid);

        fetch(lookupUrl, {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ rfid_uid: uid, location_id: locId || null }),
        })
        .then(r => r.json())
        .then(json => {
            if (!json.success) throw new Error(json.message || 'Error');
            const s = json.status;
            const k = json.kadet;
            if (s === 'pending_face') {
                setStatus('scanning', 'bi-camera-video-fill',
                    'Verifikasi Wajah...', k ? k.nama_lengkap : uid);
                openFaceModal(uid, k, json.location_id || locId);
            } else {
                updateResultCard(uid, s, k, json.pesan);
                addLogRow(uid, s, k, json.pesan);
                flashStatusBox();
            }
        })
        .catch(err => {
            setStatus('warning', 'bi-exclamation-triangle-fill', 'Gagal memeriksa UID', err.message);
        })
        .finally(() => {
            rfidInput.value = '';
            if (isScanning) rfidInput.focus();
        });
    }

    rfidInput.addEventListener('keydown', (e) => {
        clearTimeout(inputTimer);
        if (e.key === 'Enter') {
            e.preventDefault();
            triggerLookup();
        } else {
            inputTimer = setTimeout(() => {
                if (rfidInput.value.trim().length >= 4) triggerLookup();
            }, INPUT_DEBOUNCE);
        }
    });
    btnManual.addEventListener('click', triggerLookup);

    // =========================================================================
    // Face Verification Flow
    // =========================================================================
    function openFaceModal(uid, kadet, locationId) {
        currentFaceUid    = uid;
        currentFaceKadet  = kadet;
        currentLocationId = locationId || document.getElementById('locationSelect').value;

        document.getElementById('faceKadetBadge').textContent = kadet?.nama_lengkap || uid;
        document.getElementById('faceStatusMsg').textContent  = 'Menghidupkan kamera...';
        document.getElementById('btnFaceCapture').disabled    = true;
        document.getElementById('btnFaceRetry').style.display = 'none';
        document.getElementById('faceCountdownOverlay').style.display = 'none';
        document.getElementById('faceResultOverlay').style.display    = 'none';
        document.getElementById('faceGuideBox').style.display         = '';
        document.getElementById('faceVideo').style.display            = 'block';
        document.getElementById('faceVideo').style.opacity            = '1';

        const modal = new bootstrap.Modal(document.getElementById('faceModal'));
        modal.show();
        startFaceCamera();
    }

    async function startFaceCamera() {
        stopFaceCamera();
        try {
            faceStream = await navigator.mediaDevices.getUserMedia({
                video: { width: { ideal: 640 }, height: { ideal: 480 }, facingMode: 'user' },
                audio: false,
            });
            const video = document.getElementById('faceVideo');
            video.srcObject = faceStream;
            video.onloadedmetadata = () => {
                document.getElementById('faceStatusMsg').textContent =
                    'Kamera aktif — Arahkan wajah ke dalam bingkai';
                document.getElementById('btnFaceCapture').disabled = false;
                setTimeout(startFaceCountdown, 1200);
            };
        } catch (err) {
            document.getElementById('faceStatusMsg').textContent =
                'Kamera tidak dapat dibuka: ' + err.message;
            document.getElementById('btnFaceRetry').style.display = '';
        }
    }

    function startFaceCountdown() {
        let count = 3;
        const overlay = document.getElementById('faceCountdownOverlay');
        const num     = document.getElementById('faceCountdownNum');
        overlay.style.display = 'flex';
        num.textContent = count;
        document.getElementById('btnFaceCapture').disabled = true;
        document.getElementById('faceStatusMsg').textContent = 'Bersiap...';
        faceCountdownTimer = setInterval(() => {
            count--;
            if (count > 0) {
                num.textContent = count;
            } else {
                clearInterval(faceCountdownTimer);
                overlay.style.display = 'none';
                captureAndVerify();
            }
        }, 1000);
    }

    function captureAndVerify() {
        const video  = document.getElementById('faceVideo');
        const canvas = document.createElement('canvas');
        canvas.width  = video.videoWidth  || 640;
        canvas.height = video.videoHeight || 480;
        const ctx = canvas.getContext('2d');
        ctx.translate(canvas.width, 0);
        ctx.scale(-1, 1);
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const b64 = canvas.toDataURL('image/jpeg', 0.85)
                          .replace(/^data:image\/jpeg;base64,/, '');

        document.getElementById('faceGuideBox').style.display  = 'none';
        document.getElementById('faceVideo').style.opacity     = '0.25';
        document.getElementById('faceStatusMsg').textContent   = 'Memverifikasi wajah...';
        document.getElementById('btnFaceCapture').disabled     = true;

        fetch(verifyFaceUrl, {
            method: 'POST',
            headers: {
                'Content-Type':     'application/json',
                'Accept':           'application/json',
                'X-CSRF-TOKEN':     csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                rfid_uid:    currentFaceUid,
                face_image:  b64,
                location_id: currentLocationId,
            }),
        })
        .then(r => r.json())
        .then(json => {
            stopFaceCamera();
            if (!json.success) throw new Error(json.message || 'Service error');
            showFaceResultOverlay(json.status, json.confidence, json.keterangan);
            setTimeout(() => {
                closeFaceModal();
                finalizeAccess(json.status, json.kadet || currentFaceKadet,
                               json.keterangan, json.confidence, json.snapshot_url || null,
                               json.direction || null, json.presence || null);
            }, 2500);
        })
        .catch(err => {
            document.getElementById('faceVideo').style.opacity = '1';
            document.getElementById('faceGuideBox').style.display = '';
            document.getElementById('faceStatusMsg').textContent = 'Error: ' + err.message;
            document.getElementById('btnFaceRetry').style.display = '';
            document.getElementById('btnFaceCapture').disabled = false;
        });
    }

    function showFaceResultOverlay(status, confidence, keterangan) {
        const overlay = document.getElementById('faceResultOverlay');
        const icon    = document.getElementById('faceResultIcon');
        const text    = document.getElementById('faceResultText');
        const conf    = document.getElementById('faceConfidenceTxt');
        overlay.style.display = 'flex';
        if (status === 'granted') {
            overlay.style.background = 'rgba(25,135,84,.85)';
            icon.className  = 'bi bi-check-circle-fill text-white mb-2';
            text.textContent = 'AKSES DIBERIKAN';
        } else if (status === 'face_mismatch') {
            overlay.style.background = 'rgba(220,53,69,.85)';
            icon.className  = 'bi bi-x-circle-fill text-white mb-2';
            text.textContent = 'WAJAH TIDAK COCOK';
        } else {
            overlay.style.background = 'rgba(108,117,125,.85)';
            icon.className  = 'bi bi-dash-circle-fill text-white mb-2';
            text.textContent = 'AKSES DITOLAK';
        }
        conf.textContent = confidence != null ? `Confidence: ${confidence}%` : (keterangan || '');
        document.getElementById('faceStatusMsg').textContent = keterangan || '';
    }

    function stopFaceCamera() {
        if (faceStream) {
            faceStream.getTracks().forEach(t => t.stop());
            faceStream = null;
        }
        clearInterval(faceCountdownTimer);
    }

    function closeFaceModal() {
        stopFaceCamera();
        const modalEl = document.getElementById('faceModal');
        const modal   = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
    }

    function finalizeAccess(status, kadet, keterangan, confidence, snapshotUrl, direction, presence) {
        updateResultCard(currentFaceUid, status, kadet, keterangan, snapshotUrl, direction, presence);
        addLogRow(currentFaceUid, status, kadet, keterangan, null, snapshotUrl, direction);
        flashStatusBox();
        if (isScanning) setTimeout(() => rfidInput.focus(), 300);
    }

    document.getElementById('btnFaceRetry').addEventListener('click', () => {
        document.getElementById('faceResultOverlay').style.display = 'none';
        document.getElementById('faceGuideBox').style.display = '';
        document.getElementById('btnFaceRetry').style.display = 'none';
        startFaceCamera();
    });

    document.getElementById('btnFaceCancel').addEventListener('click', () => {
        closeFaceModal();
        setStatus('scanning', 'bi-upc-scan', 'Menunggu RFID...', 'Tap kartu RFID di reader');
        if (isScanning) setTimeout(() => rfidInput.focus(), 300);
    });

    document.getElementById('btnFaceCapture').addEventListener('click', () => {
        clearInterval(faceCountdownTimer);
        document.getElementById('faceCountdownOverlay').style.display = 'none';
        captureAndVerify();
    });

    document.getElementById('faceModal').addEventListener('hidden.bs.modal', stopFaceCamera);

    // =========================================================================
    // Polling log dari Python worker
    // =========================================================================
    async function poll() {
        try {
            const resp = await fetch(`${latestUrl}?after_id=${lastId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const json = await resp.json();
            if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                [...json.data].reverse().forEach(log => {
                    addLogRowFromPoll(log);
                    if (log.id > lastId) lastId = log.id;
                });
            }
        } catch (err) {
            console.warn('Poll error:', err.message);
        }
        if (isScanning) pollTimer = setTimeout(poll, POLL_MS);
    }

    // =========================================================================
    // Tombol Start / Stop
    // =========================================================================
    btnStart.addEventListener('click', () => {
        const locId = document.getElementById('locationSelect').value;
        if (!locId) { alert('Pilih lokasi/pos terlebih dahulu.'); return; }
        isScanning = true;
        btnStart.disabled     = true;
        btnStop.disabled      = false;
        indicator.className   = 'badge bg-success';
        indicator.textContent = 'AKTIF';
        rfidInputCard.style.display = '';
        rfidInput.focus();
        setStatus('scanning', 'bi-upc-scan', 'Menunggu RFID...', 'Tap kartu RFID di reader');
        poll();
    });

    btnStop.addEventListener('click', () => {
        isScanning = false;
        clearTimeout(pollTimer);
        clearTimeout(inputTimer);
        btnStart.disabled     = false;
        btnStop.disabled      = true;
        indicator.className   = 'badge bg-secondary';
        indicator.textContent = 'IDLE';
        rfidInputCard.style.display = 'none';
        rfidInput.value = '';
        setStatus('idle', 'bi-upc', 'Scan dihentikan', 'Klik MULAI SCAN untuk melanjutkan');
    });

    document.addEventListener('click', (e) => {
        if (!isScanning) return;
        const tag = e.target.tagName;
        if (['BUTTON', 'A', 'SELECT', 'INPUT', 'TEXTAREA'].includes(tag)) return;
        if (document.getElementById('faceModal').classList.contains('show')) return;
        rfidInput.focus();
    });

    document.getElementById('btnClearLog').addEventListener('click', () => {
        tbody.innerHTML = '<tr id="noLogRow"><td colspan="5" class="text-center text-muted py-4">Log dibersihkan</td></tr>';
        logCount = 0;
        logCountEl.textContent = 0;
    });

    document.getElementById('imgModal').addEventListener('show.bs.modal', (e) => {
        const img = e.relatedTarget;
        if (!img || !img.dataset.src) return;
        document.getElementById('imgModalSrc').src          = img.dataset.src;
        document.getElementById('imgModalInfo').textContent = img.dataset.info || '';
    });
}());
</script>
@endpush
