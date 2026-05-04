{{--
    File   : resources/views/penjaga/dashboard.blade.php
    Fungsi : Dashboard real-time penjaga pos — statistik hari ini + log terbaru
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.penjaga')

@section('title', 'Dashboard Penjaga')
@section('page-title', 'Dashboard')

@section('content')

{{-- Header info pos --}}
<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Dashboard Penjaga</h5>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            @if($user->location)
                Pos: <strong>{{ $user->location->nama_lokasi }}</strong> ({{ $user->location->kode_lokasi }})
            @else
                Semua Pos
            @endif
        </p>
    </div>
    <a href="{{ route('penjaga.scan.index') }}" class="btn btn-sm"
       style="background:#e94560;color:#fff;border:none;">
        <i class="bi bi-upc-scan me-1"></i>Buka SCAN
    </a>
</div>

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    <div class="col-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#1a1a2e,#0f3460);">
            <div class="stat-icon"><i class="bi bi-list-check"></i></div>
            <div class="stat-num" id="stat-total">{{ $stats['hari_ini_total'] }}</div>
            <div class="stat-label">Total Akses Hari Ini</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#198754,#20c997);">
            <div class="stat-icon"><i class="bi bi-check-circle"></i></div>
            <div class="stat-num" id="stat-granted">{{ $stats['hari_ini_granted'] }}</div>
            <div class="stat-label">Diberikan</div>
        </div>
    </div>
    <div class="col-4">
        <div class="stat-card" style="background:linear-gradient(135deg,#e94560,#c0392b);">
            <div class="stat-icon"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-num" id="stat-denied">{{ $stats['hari_ini_denied'] }}</div>
            <div class="stat-label">Gagal / Ditolak</div>
        </div>
    </div>
</div>

{{-- Log terbaru --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-clock-history me-2 text-muted"></i>Akses Terbaru (20 terakhir)</span>
        <div class="d-flex align-items-center gap-2">
            <span id="live-badge" class="badge bg-success scan-pulse" style="font-size:.7rem;display:none;">LIVE</span>
            <span id="lastUpdated" class="badge rounded-pill bg-secondary" style="font-size:.7rem;"></span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="recentTable">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Waktu</th>
                        <th>Kadet</th>
                        @if(!$user->location_id)
                            <th>Lokasi</th>
                        @endif
                        <th>Status</th>
                        <th>Posisi</th>
                        <th class="pe-3">Foto</th>
                    </tr>
                </thead>
                <tbody id="dash-tbody">
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
                                    <span class="text-muted fst-italic" style="font-size:.82rem;">Tak dikenal</span>
                                @endif
                            </td>
                            @if(!$user->location_id)
                                <td style="font-size:.82rem;">{{ $log->location?->nama_lokasi ?? '-' }}</td>
                            @endif
                            <td>
                                @php
                                    [$bc, $lbl] = match($log->status) {
                                        'granted'       => ['bg-success',           'Diberikan'],
                                        'denied'        => ['bg-danger',            'Ditolak'],
                                        'face_mismatch' => ['bg-warning text-dark', 'Wajah ≠'],
                                        'rfid_unknown'  => ['bg-secondary',         'RFID ≠'],
                                        default         => ['bg-light text-dark',   $log->status],
                                    };
                                @endphp
                                <span class="badge {{ $bc }}" style="font-size:.72rem;">{{ $lbl }}</span>
                            </td>
                            <td style="font-size:.78rem;">
                                @if($log->kadet_id && isset($presences[$log->kadet_id]))
                                    @php $pres = $presences[$log->kadet_id]; @endphp
                                    @if($pres->current_location_id !== null)
                                        @if($pres->currentLocation?->location_type === 'gate')
                                            <span class="badge bg-primary" style="font-size:.7rem;">
                                                <i class="bi bi-geo-alt-fill"></i> Dalam Ksatrian
                                            </span>
                                        @else
                                            <span class="badge bg-danger" style="font-size:.7rem;">
                                                <i class="bi bi-building"></i> {{ $pres->currentLocation->nama_lokasi }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="badge bg-secondary" style="font-size:.7rem;">
                                            <i class="bi bi-geo"></i> Di luar
                                        </span>
                                    @endif
                                @elseif($log->kadet_id)
                                    <span class="badge bg-secondary" style="font-size:.7rem;">
                                        <i class="bi bi-geo"></i> Di luar
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="pe-3">
                                @if($log->gambar_path)
                                    <img src="{{ asset('storage/'.$log->gambar_path) }}"
                                         alt="Foto" width="36" height="36"
                                         class="rounded border object-fit-cover"
                                         style="cursor:pointer;"
                                         data-bs-toggle="modal"
                                         data-bs-target="#imgModal"
                                         data-src="{{ asset('storage/'.$log->gambar_path) }}"
                                         data-waktu="{{ $log->waktu_akses?->format('d/m/Y H:i') }}"
                                         onerror="this.parentElement.innerHTML='<span class=&quot;text-muted&quot;>&mdash;</span>'">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="emptyRow">
                            <td colspan="6" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>Belum ada akses hari ini
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Snapshot modal --}}
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header py-2">
                <small class="text-muted" id="imgModalWaktu"></small>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <img id="imgModalSrc" src="" alt="Snapshot" class="img-fluid w-100"
                     style="border-radius:0 0 .375rem .375rem;">
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

    const statsUrl = '{{ route("penjaga.dashboard.stats") }}';
    const showLoc  = {{ $user->location_id ? 'false' : 'true' }};
    const POLL_MS  = 5000;
    let   lastId   = {{ $recentLogs->first()?->id ?? 0 }};

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

    function posisiHtml(posisi, isInside) {
        if (!posisi) return '<span class="text-muted">—</span>';
        if (!isInside) {
            return `<span class="badge bg-secondary" style="font-size:.7rem;"><i class="bi bi-geo"></i> Di luar</span>`;
        }
        if (posisi === 'Di dalam ksatrian') {
            return `<span class="badge bg-primary" style="font-size:.7rem;"><i class="bi bi-geo-alt-fill"></i> Dalam Ksatrian</span>`;
        }
        // In a room
        const nama = posisi.replace(/^Di /, '');
        return `<span class="badge bg-danger" style="font-size:.7rem;"><i class="bi bi-building"></i> ${nama}</span>`;
    }

    function updateStats(s) {
        document.getElementById('stat-total').textContent   = s.hari_ini_total;
        document.getElementById('stat-granted').textContent = s.hari_ini_granted;
        document.getElementById('stat-denied').textContent  = s.hari_ini_denied;
    }

    function prependRow(log) {
        const tbody = document.getElementById('dash-tbody');
        const empty = tbody.querySelector('td[colspan]');
        if (empty) empty.closest('tr').remove();

        const tr = document.createElement('tr');
        tr.className = 'log-new';
        const locTd = showLoc ? `<td style="font-size:.82rem;">${log.lokasi}</td>` : '';
        const snapTd = log.gambar_url
            ? `<img src="${log.gambar_url}" alt="Foto" width="36" height="36"
                    class="rounded border object-fit-cover" style="cursor:pointer;"
                    data-bs-toggle="modal" data-bs-target="#imgModal"
                    data-src="${log.gambar_url}" data-waktu="${log.waktu}"
                    onerror="this.parentElement.innerHTML='<span class=&quot;text-muted&quot;>&mdash;</span>'">` 
            : '<span class="text-muted">—</span>';

        tr.innerHTML = `
            <td class="ps-3 text-nowrap" style="font-size:.8rem;">${log.waktu}</td>
            <td>
                ${log.nama
                    ? `<div class="fw-semibold" style="font-size:.85rem;">${log.nama}</div>
                       <div class="text-muted" style="font-size:.75rem;">${log.nim || ''}</div>`
                    : `<span class="text-muted fst-italic" style="font-size:.82rem;">Tak dikenal</span>`}
            </td>
            ${locTd}
            <td>${badgeHtml(log.status)}</td>
            <td style="font-size:.78rem;">${posisiHtml(log.posisi, log.is_inside)}</td>
            <td class="pe-3">${snapTd}</td>`;

        tbody.insertBefore(tr, tbody.firstChild);
        while (tbody.rows.length > 20) tbody.deleteRow(tbody.rows.length - 1);
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
                [...json.logs].reverse().forEach(log => {
                    prependRow(log);
                    if (log.id > lastId) lastId = log.id;
                });
                const badge = document.getElementById('live-badge');
                badge.style.display = '';
                setTimeout(() => { badge.style.display = 'none'; }, 2500);
            }

            document.getElementById('lastUpdated').textContent =
                'Update: ' + new Date().toLocaleTimeString('id-ID');
        } catch (e) { /* silent */ }

        setTimeout(poll, POLL_MS);
    }

    // Snapshot modal
    document.getElementById('imgModal').addEventListener('show.bs.modal', (e) => {
        const img = e.relatedTarget;
        document.getElementById('imgModalSrc').src = img.dataset.src || '';
        document.getElementById('imgModalWaktu').textContent = img.dataset.waktu || '';
    });

    // Init
    document.getElementById('lastUpdated').textContent = 'Update: ' + new Date().toLocaleTimeString('id-ID');
    setTimeout(poll, POLL_MS);
}());
</script>
@endpush
