{{--
    File   : resources/views/admin/presence/index.blade.php
    Fungsi : Monitoring kehadiran kadet realtime — siapa saja yang sedang di dalam area
    Author : SAKTI Dev Team
    Date   : 2026-05-04
--}}
@extends('layouts.admin')

@section('title', 'Monitoring Kehadiran')
@section('page-title', 'Monitoring Kehadiran Kadet')

@push('styles')
<style>
    .presence-card {
        transition: box-shadow .2s;
    }
    .presence-card:hover {
        box-shadow: 0 4px 15px rgba(233,69,96,.2);
    }
    .kadet-avatar {
        width: 48px; height: 48px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #dee2e6;
        flex-shrink: 0;
    }
    .location-badge-gate { background: #1a1a2e; color: #fff; }
    .location-badge-room { background: #e94560; color: #fff; }
    .pulse-dot {
        width: 8px; height: 8px; border-radius: 50%;
        background: #198754;
        display: inline-block;
        animation: blink 1.5s infinite;
    }
    @keyframes blink {
        0%, 100% { opacity: 1; }
        50% { opacity: .25; }
    }
    #lastUpdated { font-size: .75rem; }
</style>
@endpush

@section('content')

{{-- ===== HEADER STATS ===== --}}
<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="fw-bold fs-4" id="totalInside">—</div>
                <div class="text-muted" style="font-size:.78rem;">Kadet di Dalam Area</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center">
            <div class="card-body py-2">
                <div class="fw-bold fs-4">{{ $totalKadet }}</div>
                <div class="text-muted" style="font-size:.78rem;">Total Kadet Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 d-flex align-items-center justify-content-md-end gap-2">
        <span class="pulse-dot"></span>
        <span class="text-muted" style="font-size:.82rem;">Live Update setiap 5 detik</span>
        <span id="lastUpdated" class="badge bg-secondary ms-2">—</span>
    </div>
</div>

{{-- ===== RINGKASAN PER LOKASI ===== --}}
<div class="row g-2 mb-3" id="locationSummary">
    {{-- diisi JS --}}
</div>

{{-- ===== FILTER + TABEL ===== --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-people me-2 text-muted"></i>Daftar Kadet di Dalam Area</span>
        <div class="d-flex gap-2 align-items-center">
            <select id="filterLocation" class="form-select form-select-sm" style="width:auto;">
                <option value="">Semua Lokasi</option>
                @foreach($locations as $loc)
                    <option value="{{ $loc->id }}">{{ $loc->nama_lokasi }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Kadet</th>
                        <th>NIM</th>
                        <th>Prodi</th>
                        <th>Lokasi Sekarang</th>
                        <th>Masuk Sejak</th>
                        <th>Durasi</th>
                    </tr>
                </thead>
                <tbody id="presenceTableBody">
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-hourglass-split fs-2 d-block mb-2"></i>
                            Memuat data...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-transparent d-flex justify-content-between align-items-center"
         style="font-size:.78rem;">
        <span class="text-muted">Menampilkan <strong id="showingCount">0</strong> kadet</span>
        <button class="btn btn-sm btn-outline-secondary" id="btnRefresh">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
        </button>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const dataUrl   = '{{ route("admin.presence.data") }}';
    const POLL_MS   = 5000;
    let pollTimer   = null;

    const tbody         = document.getElementById('presenceTableBody');
    const totalInsideEl = document.getElementById('totalInside');
    const lastUpdatedEl = document.getElementById('lastUpdated');
    const showingCount  = document.getElementById('showingCount');
    const filterLoc     = document.getElementById('filterLocation');
    const summaryRow    = document.getElementById('locationSummary');

    function fotoHtml(url, nama) {
        const fallback = `https://ui-avatars.com/api/?name=${encodeURIComponent(nama||'?')}&size=48&background=1a1a2e&color=e94560`;
        return `<img src="${url || fallback}" class="kadet-avatar" alt="${nama}" onerror="this.src='${fallback}'">`;
    }

    function locationTypeBadge(type, nama) {
        const cls = type === 'gate' ? 'location-badge-gate' : 'location-badge-room';
        const icon = type === 'gate' ? 'bi-door-open' : 'bi-building';
        return `<span class="badge ${cls}" style="font-size:.72rem;"><i class="bi ${icon} me-1"></i>${nama}</span>`;
    }

    function renderSummary(summary) {
        if (!summary.length) {
            summaryRow.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info mb-0" style="font-size:.85rem;">
                        <i class="bi bi-info-circle me-2"></i>Tidak ada kadet di dalam area saat ini.
                    </div>
                </div>`;
            return;
        }
        summaryRow.innerHTML = summary.map(s => `
            <div class="col-6 col-md-4 col-lg-3">
                <div class="card presence-card text-center">
                    <div class="card-body py-2">
                        <div class="fw-bold fs-5 ${s.location_type === 'gate' ? 'text-primary' : 'text-danger'}">${s.jumlah}</div>
                        <div style="font-size:.75rem;" class="text-muted">${s.nama_lokasi}</div>
                        <span class="badge ${s.location_type === 'gate' ? 'bg-primary' : 'bg-danger'} mt-1" style="font-size:.65rem;">
                            ${s.location_type === 'gate' ? 'Gerbang' : 'Ruangan'}
                        </span>
                    </div>
                </div>
            </div>`).join('');
    }

    function renderTable(presences) {
        if (!presences.length) {
            tbody.innerHTML = `
                <tr><td colspan="6" class="text-center text-muted py-4">
                    <i class="bi bi-person-slash fs-2 d-block mb-2"></i>
                    Tidak ada kadet di dalam area${filterLoc.value ? ' pada lokasi ini' : ''}.
                </td></tr>`;
            showingCount.textContent = 0;
            return;
        }
        tbody.innerHTML = presences.map(p => `
            <tr>
                <td class="ps-3">
                    <div class="d-flex align-items-center gap-2">
                        ${fotoHtml(p.foto_url, p.nama_lengkap)}
                        <div>
                            <div class="fw-semibold" style="font-size:.85rem;">${p.nama_lengkap}</div>
                        </div>
                    </div>
                </td>
                <td style="font-size:.83rem;">${p.nim}</td>
                <td style="font-size:.82rem;" class="text-muted">${p.prodi}</td>
                <td>${locationTypeBadge(p.lokasi_type, p.lokasi)}</td>
                <td style="font-size:.8rem;" class="text-nowrap">${p.entered_at || '-'}</td>
                <td style="font-size:.8rem;" class="text-muted">${p.durasi}</td>
            </tr>`).join('');
        showingCount.textContent = presences.length;
    }

    async function fetchData() {
        try {
            const locId = filterLoc.value || '';
            const resp  = await fetch(`${dataUrl}?location_id=${locId}`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
            const json = await resp.json();
            if (!json.success) throw new Error('Server error');

            totalInsideEl.textContent = json.total_inside;
            lastUpdatedEl.textContent = 'Update: ' + json.generated_at;
            renderSummary(json.summary || []);
            renderTable(json.presences || []);
        } catch (err) {
            console.warn('Presence fetch error:', err.message);
        }
        pollTimer = setTimeout(fetchData, POLL_MS);
    }

    filterLoc.addEventListener('change', () => {
        clearTimeout(pollTimer);
        fetchData();
    });

    document.getElementById('btnRefresh').addEventListener('click', () => {
        clearTimeout(pollTimer);
        fetchData();
    });

    // Start polling
    fetchData();
}());
</script>
@endpush
