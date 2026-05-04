{{--
    File   : resources/views/admin/reports/index.blade.php
    Fungsi : Laporan log akses — filter tanggal/lokasi/status, thumbnail modal,
             pagination, dan export PDF
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Laporan Akses')
@section('page-title', 'Laporan Log Akses')

@section('content')

{{-- ===== FILTER FORM ===== --}}
<div class="card mb-3">
    <div class="card-header"><i class="bi bi-funnel me-2 text-muted"></i>Filter</div>
    <div class="card-body">
        <form method="GET" action="{{ route('admin.reports.index') }}" id="filterForm">
            <div class="row g-2 align-items-end">
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label mb-1" style="font-size:.8rem;">Dari Tanggal</label>
                    <input type="date" name="dari_tanggal" class="form-control form-control-sm"
                           value="{{ request('dari_tanggal') }}">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label mb-1" style="font-size:.8rem;">Sampai Tanggal</label>
                    <input type="date" name="sampai_tanggal" class="form-control form-control-sm"
                           value="{{ request('sampai_tanggal') }}">
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label mb-1" style="font-size:.8rem;">Lokasi</label>
                    <select name="location_id" class="form-select form-select-sm">
                        <option value="">Semua Lokasi</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc->id }}"
                                {{ request('location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->nama_lokasi }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-md-3 col-xl-2">
                    <label class="form-label mb-1" style="font-size:.8rem;">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Semua Status</option>
                        <option value="granted"       {{ request('status') === 'granted'       ? 'selected' : '' }}>Diberikan</option>
                        <option value="denied"        {{ request('status') === 'denied'        ? 'selected' : '' }}>Ditolak</option>
                        <option value="face_mismatch" {{ request('status') === 'face_mismatch' ? 'selected' : '' }}>Wajah ≠</option>
                        <option value="rfid_unknown"  {{ request('status') === 'rfid_unknown'  ? 'selected' : '' }}>RFID Tak Dikenal</option>
                    </select>
                </div>
                <div class="col-12 col-md-12 col-xl-4 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-search me-1"></i>Filter
                    </button>
                    @if(request()->hasAny(['dari_tanggal','sampai_tanggal','location_id','status']))
                        <a href="{{ route('admin.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-x me-1"></i>Reset
                        </a>
                    @endif
                    <a href="{{ route('admin.reports.export-pdf', request()->query()) }}"
                       class="btn btn-sm btn-outline-danger ms-auto" target="_blank">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- ===== STATS ===== --}}
<div class="row g-2 mb-3">
    @php
        $statItems = [
            ['label' => 'Total',          'val' => $stats['total'],         'cls' => 'bg-secondary'],
            ['label' => 'Diberikan',       'val' => $stats['granted'],       'cls' => 'bg-success'],
            ['label' => 'Ditolak',         'val' => $stats['denied'],        'cls' => 'bg-danger'],
            ['label' => 'Wajah Tidak Cocok','val' => $stats['face_mismatch'],'cls' => 'bg-warning text-dark'],
            ['label' => 'RFID Tak Dikenal','val' => $stats['rfid_unknown'],  'cls' => 'bg-dark'],
        ];
    @endphp
    @foreach($statItems as $si)
        <div class="col-6 col-sm-4 col-lg">
            <div class="card text-center">
                <div class="card-body py-2">
                    <div class="fw-bold fs-5">{{ $si['val'] }}</div>
                    <span class="badge {{ $si['cls'] }}" style="font-size:.7rem;">{{ $si['label'] }}</span>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- ===== TABLE ===== --}}
<div class="card">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-table me-2 text-muted"></i>Log Akses</span>
        <span class="text-muted" style="font-size:.8rem;">{{ $logs->total() }} record</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Waktu</th>
                        <th>Kadet</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Arah</th>
                        <th>Foto</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        <tr>
                            <td class="ps-3 text-nowrap" style="font-size:.8rem;">
                                {{ $log->waktu_akses?->format('d/m/Y') }}<br>
                                <span class="text-muted">{{ $log->waktu_akses?->format('H:i:s') }}</span>
                            </td>
                            <td>
                                @if($log->kadet)
                                    <div class="fw-semibold" style="font-size:.85rem;">{{ $log->kadet->nama_lengkap }}</div>
                                    <div class="text-muted" style="font-size:.75rem;">{{ $log->kadet->nim }}</div>
                                @else
                                    <code class="text-danger" style="font-size:.8rem;">{{ $log->rfid_uid }}</code>
                                @endif
                            </td>
                            <td style="font-size:.82rem;">{{ $log->location?->nama_lokasi ?? '-' }}</td>
                            <td>
                                @php
                                    [$bc, $lbl] = match($log->status) {
                                        'granted'       => ['bg-success',                'Diberikan'],
                                        'denied'        => ['bg-danger',                 'Ditolak'],
                                        'face_mismatch' => ['bg-warning text-dark',      'Wajah ≠'],
                                        'rfid_unknown'  => ['bg-secondary',              'RFID ≠'],
                                        default         => ['bg-light text-dark border', ucfirst($log->status)],
                                    };
                                @endphp
                                <span class="badge {{ $bc }}" style="font-size:.72rem;">{{ $lbl }}</span>
                            </td>
                            <td>
                                @if($log->direction === 'masuk')
                                    <span class="badge bg-primary" style="font-size:.7rem;">
                                        <i class="bi bi-box-arrow-in-right"></i> MASUK
                                    </span>
                                @elseif($log->direction === 'keluar')
                                    <span class="badge bg-warning text-dark" style="font-size:.7rem;">
                                        <i class="bi bi-box-arrow-right"></i> KELUAR
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size:.78rem;">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                @if($log->gambar_path)
                                    <img src="{{ asset('storage/'.$log->gambar_path) }}"
                                         alt="Foto"
                                         width="40" height="40"
                                         class="rounded border object-fit-cover"
                                         style="cursor:pointer;"
                                         data-bs-toggle="modal"
                                         data-bs-target="#imgModal"
                                         data-src="{{ asset('storage/'.$log->gambar_path) }}"
                                         data-kadet="{{ $log->kadet?->nama_lengkap ?? 'Tidak Dikenal' }}"
                                         data-waktu="{{ $log->waktu_akses?->format('d/m/Y H:i:s') }}"
                                         onerror="this.parentElement.innerHTML='<span class=&quot;text-muted&quot; style=&quot;font-size:.78rem;&quot;>&mdash;</span>'">
                                @else
                                    <span class="text-muted" style="font-size:.78rem;">&mdash;</span>
                                @endif
                            </td>
                            <td class="text-muted" style="font-size:.78rem;max-width:220px;">
                                {{ Str::limit($log->keterangan, 70) ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                Tidak ada data untuk filter yang dipilih
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($logs->hasPages())
        <div class="card-footer bg-transparent border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span class="text-muted" style="font-size:.8rem;">
                Menampilkan {{ $logs->firstItem() }}–{{ $logs->lastItem() }} dari {{ $logs->total() }}
            </span>
            {{ $logs->withQueryString()->links() }}
        </div>
    @endif
</div>

{{-- Snapshot Preview Modal --}}
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header py-2">
                <div>
                    <div class="fw-semibold" id="imgModalKadet" style="font-size:.9rem;"></div>
                    <small class="text-muted" id="imgModalWaktu"></small>
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <img id="imgModalSrc" src="" alt="Snapshot" class="img-fluid w-100"
                     style="border-radius:0 0 .375rem .375rem; max-height:500px; object-fit:contain;">
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const imgModal = document.getElementById('imgModal');
    imgModal.addEventListener('show.bs.modal', (e) => {
        const img = e.relatedTarget;
        document.getElementById('imgModalSrc').src     = img.dataset.src;
        document.getElementById('imgModalKadet').textContent = img.dataset.kadet;
        document.getElementById('imgModalWaktu').textContent = img.dataset.waktu;
    });
</script>
@endpush
