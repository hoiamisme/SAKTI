{{--
    File   : resources/views/admin/dashboard.blade.php
    Fungsi : Dashboard ringkasan statistik sistem SAKTI untuk admin
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Dashboard Admin')
@section('page-title', 'Dashboard')

@section('content')

{{-- Stat cards row --}}
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#1a1a2e,#0f3460);">
            <div class="stat-icon"><i class="bi bi-person-badge"></i></div>
            <div class="stat-num" id="stat-total-kadets">{{ $stats['total_kadets'] }}</div>
            <div class="stat-label">Total Kadet Aktif</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#0f3460,#16213e);">
            <div class="stat-icon"><i class="bi bi-geo-alt"></i></div>
            <div class="stat-num" id="stat-total-locations">{{ $stats['total_locations'] }}</div>
            <div class="stat-label">Pos Aktif</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#198754,#20c997);">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-num" id="stat-granted">{{ $stats['hari_ini_granted'] }}</div>
            <div class="stat-label">Akses Diberikan (Hari Ini)</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card" style="background: linear-gradient(135deg,#e94560,#c0392b);">
            <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
            <div class="stat-num" id="stat-denied">{{ $stats['hari_ini_denied'] + $stats['rfid_unknown'] }}</div>
            <div class="stat-label">Akses Gagal (Hari Ini)</div>
        </div>
    </div>
</div>

<div class="row g-3">
    {{-- Recent access logs --}}
    <div class="col-12 col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-clock-history me-2 text-muted"></i>Akses Terbaru</span>
                <div class="d-flex align-items-center gap-2">
                    <span id="dash-indicator" class="badge bg-success scan-pulse" style="font-size:.7rem;display:none;">LIVE</span>
                    <span id="dash-updated" class="text-muted" style="font-size:.75rem;"></span>
                    <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                        Lihat Semua
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Waktu</th>
                                <th>Kadet</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                                <th class="pe-3">Foto CCTV</th>
                            </tr>
                        </thead>
                        <tbody id="dash-log-tbody">
                            @forelse($recentLogs as $log)
                                <tr>
                                    <td class="ps-3 text-nowrap" style="font-size:.8rem;">
                                        {{ $log->waktu_akses?->format('d/m H:i:s') ?? '-' }}
                                    </td>
                                    <td>
                                        @if($log->kadet)
                                            <div class="fw-semibold" style="font-size:.85rem;">{{ $log->kadet->nama_lengkap }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">{{ $log->kadet->nim }}</div>
                                        @else
                                            <span class="text-muted fst-italic" style="font-size:.85rem;">Tidak dikenal</span>
                                        @endif
                                    </td>
                                    <td class="text-muted" style="font-size:.85rem;">
                                        {{ $log->location?->nama_lokasi ?? '-' }}
                                    </td>
                                    <td>
                                        @php
                                            $badgeClass = match($log->status) {
                                                'granted'       => 'bg-success',
                                                'denied'        => 'bg-danger',
                                                'face_mismatch' => 'bg-warning text-dark',
                                                default         => 'bg-secondary',
                                            };
                                            $statusLabel = match($log->status) {
                                                'granted'       => 'Diberikan',
                                                'denied'        => 'Ditolak',
                                                'face_mismatch' => 'Wajah ≠',
                                                'rfid_unknown'  => 'RFID ≠',
                                                default         => $log->status,
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }}" style="font-size:.72rem;">
                                            {{ $statusLabel }}
                                        </span>
                                    </td>
                                    <td class="pe-3">
                                        @if($log->gambar_path)
                                            <img src="{{ asset('storage/'.$log->gambar_path) }}"
                                                 alt="CCTV" width="36" height="36"
                                                 class="rounded border object-fit-cover"
                                                 style="cursor:pointer;"
                                                 data-bs-toggle="modal"
                                                 data-bs-target="#imgModal"
                                                 data-src="{{ asset('storage/'.$log->gambar_path) }}"
                                                 data-kadet="{{ $log->kadet?->nama_lengkap ?? 'Tidak Dikenal' }}"
                                                 data-waktu="{{ $log->waktu_akses?->format('d/m/Y H:i:s') }}"
                                                 onerror="this.parentElement.innerHTML='<span class=&quot;text-muted&quot;>&mdash;</span>'">
                                        @else
                                            <span class="text-muted">&mdash;</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-3 d-block mb-2"></i>Belum ada data akses
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Summary & quick links --}}
    <div class="col-12 col-xl-4">
        {{-- Donut summary --}}
        <div class="card mb-3">
            <div class="card-header">
                <i class="bi bi-bar-chart-fill me-2 text-muted"></i>Hari Ini
            </div>
            <div class="card-body">
                @php $total = $stats['hari_ini_total'] ?: 1; @endphp
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1" style="font-size:.82rem;">
                        <span><i class="bi bi-check-circle-fill text-success me-1"></i>Diberikan</span>
                        <span id="side-granted">{{ $stats['hari_ini_granted'] }}</span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar bg-success" id="bar-granted"
                             style="width:{{ round($stats['hari_ini_granted']/max($stats['hari_ini_total'],1)*100) }}%"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1" style="font-size:.82rem;">
                        <span><i class="bi bi-x-circle-fill text-danger me-1"></i>Ditolak</span>
                        <span id="side-denied">{{ $stats['hari_ini_denied'] }}</span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar bg-danger" id="bar-denied"
                             style="width:{{ round($stats['hari_ini_denied']/max($stats['hari_ini_total'],1)*100) }}%"></div>
                    </div>
                </div>
                <div class="mb-0">
                    <div class="d-flex justify-content-between mb-1" style="font-size:.82rem;">
                        <span><i class="bi bi-exclamation-circle-fill text-secondary me-1"></i>RFID Tak Dikenal</span>
                        <span id="side-rfid">{{ $stats['rfid_unknown'] }}</span>
                    </div>
                    <div class="progress" style="height:6px;">
                        <div class="progress-bar bg-secondary" id="bar-rfid"
                             style="width:{{ round($stats['rfid_unknown']/max($stats['hari_ini_total'],1)*100) }}%"></div>
                    </div>
                </div>
                <div class="mt-3 pt-2 border-top text-center">
                    <span class="text-muted" style="font-size:.8rem;">Total hari ini:</span>
                    <span class="fw-bold ms-1" id="side-total">{{ $stats['hari_ini_total'] }}</span>
                </div>
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-lightning me-2 text-muted"></i>Aksi Cepat</div>
            <div class="card-body d-grid gap-2">
                <a href="{{ route('admin.kadets.create') }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-person-plus me-2"></i>Tambah Kadet Baru
                </a>
                <a href="{{ route('admin.permissions.index') }}" class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-key me-2"></i>Kelola Hak Akses
                </a>
                <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Lihat Laporan
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Modal foto CCTV --}}
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="background:#111;">
            <div class="modal-header border-0 pb-0">
                <div>
                    <div class="fw-semibold text-white" id="imgModalKadet" style="font-size:.9rem;"></div>
                    <small class="text-muted" id="imgModalWaktu"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center pt-2">
                <img id="imgModalSrc" src="" alt="Snapshot CCTV" class="img-fluid w-100"
                     style="max-height:70vh;object-fit:contain;border-radius:6px;">
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<style>
    .scan-pulse { animation: pulse 1.5s infinite; }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .log-new { animation: slideIn .35s ease; }
    @keyframes slideIn { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
</style>
<script>
(function () {
    'use strict';

    const statsUrl  = '{{ route("admin.dashboard.stats") }}';
    const POLL_MS   = 5000;
    let   lastId    = {{ $recentLogs->first()?->id ?? 0 }};

    const badgeCfg = {
        granted:       ['bg-success',           'Diberikan'],
        denied:        ['bg-danger',            'Ditolak'],
        face_mismatch: ['bg-warning text-dark', 'Wajah ≠'],
        rfid_unknown:  ['bg-secondary',         'RFID ≠'],
    };

    function badgeHtml(status) {
        const [cls, lbl] = badgeCfg[status] || ['bg-light text-dark', status];
        return `<span class="badge ${cls}" style="font-size:.72rem;">${lbl}</span>`;
    }

    function updateStats(s) {
        document.getElementById('stat-total-kadets').textContent    = s.total_kadets;
        document.getElementById('stat-total-locations').textContent = s.total_locations;
        document.getElementById('stat-granted').textContent         = s.hari_ini_granted;
        document.getElementById('stat-denied').textContent          = s.hari_ini_denied + s.rfid_unknown;

        // Side panel
        const total = Math.max(s.hari_ini_total, 1);
        document.getElementById('side-total').textContent   = s.hari_ini_total;
        document.getElementById('side-granted').textContent = s.hari_ini_granted;
        document.getElementById('side-denied').textContent  = s.hari_ini_denied;
        document.getElementById('side-rfid').textContent    = s.rfid_unknown;
        document.getElementById('bar-granted').style.width  = Math.round(s.hari_ini_granted / total * 100) + '%';
        document.getElementById('bar-denied').style.width   = Math.round(s.hari_ini_denied  / total * 100) + '%';
        document.getElementById('bar-rfid').style.width     = Math.round(s.rfid_unknown     / total * 100) + '%';
    }

    function prependRow(log) {
        const tbody = document.getElementById('dash-log-tbody');
        // Hapus baris "Belum ada data" kalau masih ada
        const empty = tbody.querySelector('td[colspan]');
        if (empty) empty.closest('tr').remove();

        const tr = document.createElement('tr');
        tr.className = 'log-new';
        const snapTd = log.gambar_url
            ? `<img src="${log.gambar_url}" alt="CCTV" width="36" height="36"
                    class="rounded border object-fit-cover" style="cursor:pointer;"
                    data-bs-toggle="modal" data-bs-target="#imgModal"
                    data-src="${log.gambar_url}"
                    data-kadet="${log.nama || 'Tidak Dikenal'}"
                    data-waktu="${log.waktu}"
                    onerror="this.parentElement.innerHTML='<span class=&quot;text-muted&quot;>&mdash;</span>'">`
            : '<span class="text-muted">&mdash;</span>';

        tr.innerHTML = `
            <td class="ps-3 text-nowrap" style="font-size:.8rem;">${log.waktu}</td>
            <td>
                ${log.nama
                    ? `<div class="fw-semibold" style="font-size:.85rem;">${log.nama}</div>
                       <div class="text-muted" style="font-size:.75rem;">${log.nim || ''}</div>`
                    : `<span class="text-muted fst-italic" style="font-size:.85rem;">Tidak dikenal</span>`}
            </td>
            <td class="text-muted" style="font-size:.85rem;">${log.lokasi}</td>
            <td>${badgeHtml(log.status)}</td>
            <td class="pe-3">${snapTd}</td>`;
        tbody.insertBefore(tr, tbody.firstChild);

        // Batasi max 10 baris
        while (tbody.rows.length > 10) tbody.deleteRow(tbody.rows.length - 1);
    }

    async function poll() {
        try {
            const resp = await fetch(`${statsUrl}?after_id=${lastId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) return;
            const json = await resp.json();
            if (!json.success) return;

            updateStats(json.stats);

            if (json.logs && json.logs.length > 0) {
                // Urutkan dari terlama ke terbaru lalu prepend (agar terbaru di atas)
                [...json.logs].reverse().forEach(log => {
                    prependRow(log);
                    if (log.id > lastId) lastId = log.id;
                });
                // Flash indikator LIVE sebentar
                const ind = document.getElementById('dash-indicator');
                ind.style.display = '';
                setTimeout(() => { ind.style.display = 'none'; }, 2500);
            }

            document.getElementById('dash-updated').textContent =
                'Update: ' + new Date().toLocaleTimeString('id-ID');
        } catch (e) { /* silent */ }

        setTimeout(poll, POLL_MS);
    }

    // Modal foto CCTV
    document.getElementById('imgModal').addEventListener('show.bs.modal', (e) => {
        const img = e.relatedTarget;
        if (!img || !img.dataset.src) return;
        document.getElementById('imgModalSrc').src              = img.dataset.src;
        document.getElementById('imgModalKadet').textContent    = img.dataset.kadet || '';
        document.getElementById('imgModalWaktu').textContent    = img.dataset.waktu || '';
    });

    // Mulai polling setelah halaman load
    setTimeout(poll, POLL_MS);
    document.getElementById('dash-updated').textContent = 'Update: ' + new Date().toLocaleTimeString('id-ID');
}());
</script>
@endpush
