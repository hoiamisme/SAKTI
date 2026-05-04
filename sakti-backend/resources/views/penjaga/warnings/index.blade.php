{{--
    File   : resources/views/penjaga/warnings/index.blade.php
    Fungsi : Daftar akses gagal hari ini di pos penjaga — denied, face_mismatch,
             rfid_unknown. Diperbarui setiap 30 detik via JS reload.
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.penjaga')

@section('title', 'Peringatan Akses')
@section('page-title', 'Peringatan Akses')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Peringatan Hari Ini</h5>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            {{ $warnings->total() }} percobaan gagal ·
            {{ now()->translatedFormat('d F Y') }}
        </p>
    </div>
    <div class="d-flex align-items-center gap-2">
        @php $totalUnread = $warnings->getCollection()->where('is_read', false)->count(); @endphp
        @if($warnings->total() > 0)
            <button id="btnReadAll" class="btn btn-sm btn-outline-primary"
                    title="Tandai semua dibaca"
                    {{ $totalUnread === 0 ? 'disabled' : '' }}>
                <i class="bi bi-check2-all me-1"></i>Tandai Semua Dibaca
                @if($totalUnread > 0)
                    <span class="badge bg-primary ms-1" id="unreadCount">{{ $totalUnread }}</span>
                @endif
            </button>
        @endif
        <span id="autoRefreshBadge" class="badge bg-success" style="font-size:.7rem;">
            <i class="bi bi-arrow-clockwise me-1"></i>Auto-refresh 30s
        </span>
        <button class="btn btn-sm btn-outline-secondary" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    </div>
</div>

@if($warnings->total() > 0)
    {{-- Summary badges --}}
    @php
        $denied  = $warnings->getCollection()->where('status','denied')->count();
        $face    = $warnings->getCollection()->where('status','face_mismatch')->count();
        $rfid    = $warnings->getCollection()->where('status','rfid_unknown')->count();
    @endphp
    <div class="d-flex gap-2 mb-3 flex-wrap">
        <span class="badge bg-danger py-2 px-3"><i class="bi bi-x-circle me-1"></i>Ditolak: {{ $denied }}</span>
        <span class="badge bg-warning text-dark py-2 px-3"><i class="bi bi-exclamation-triangle me-1"></i>Wajah ≠: {{ $face }}</span>
        <span class="badge bg-secondary py-2 px-3"><i class="bi bi-question-circle me-1"></i>RFID Tak Dikenal: {{ $rfid }}</span>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Waktu</th>
                        <th>Identitas</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Foto</th>
                        <th>Keterangan</th>
                        <th class="pe-3 text-center">Baca</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($warnings as $log)
                        @php
                            $isUnread = ! $log->is_read;
                            $rowCls   = $isUnread
                                ? match($log->status) {
                                    'denied'        => 'table-danger',
                                    'face_mismatch' => 'table-warning',
                                    default         => 'table-secondary',
                                  }
                                : '';   // baris dibaca — putih/normal
                        @endphp
                        <tr class="{{ $rowCls }} warning-row" data-id="{{ $log->id }}"
                            style="{{ $isUnread ? '' : 'opacity:.65;' }}">
                            <td class="ps-3 text-nowrap fw-semibold" style="font-size:.82rem;">
                                @if($isUnread)
                                    <span class="me-1" style="color:var(--accent);" title="Belum dibaca">●</span>
                                @endif
                                {{ $log->waktu_akses?->format('H:i:s') ?? '-' }}
                            </td>
                            <td>
                                @if($log->kadet)
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $log->kadet->foto_path
                                                    ? asset('storage/'.$log->kadet->foto_path)
                                                    : 'https://ui-avatars.com/api/?name='.urlencode($log->kadet->nama_lengkap).'&size=32&background=e94560&color=fff' }}"
                                             alt="" width="32" height="32"
                                             class="rounded-circle object-fit-cover border">
                                        <div>
                                            <div class="fw-semibold" style="font-size:.85rem;">{{ $log->kadet->nama_lengkap }}</div>
                                            <div class="text-muted" style="font-size:.75rem;">{{ $log->kadet->nim }}</div>
                                        </div>
                                    </div>
                                @else
                                    <div>
                                        <div class="text-muted fst-italic" style="font-size:.82rem;">Tidak Dikenal</div>
                                        <code style="font-size:.75rem;">{{ $log->rfid_uid }}</code>
                                    </div>
                                @endif
                            </td>
                            <td style="font-size:.82rem;">{{ $log->location?->nama_lokasi ?? '-' }}</td>
                            <td>
                                @php
                                    [$bc, $lbl] = match($log->status) {
                                        'denied'        => ['bg-danger',            'DITOLAK'],
                                        'face_mismatch' => ['bg-warning text-dark', 'WAJAH ≠'],
                                        'rfid_unknown'  => ['bg-secondary',         'RFID ≠'],
                                        default         => ['bg-light text-dark',   $log->status],
                                    };
                                @endphp
                                <span class="badge {{ $bc }}" style="font-size:.72rem;">{{ $lbl }}</span>
                            </td>
                            <td>
                                @if($log->gambar_path)
                                    <img src="{{ asset('storage/'.$log->gambar_path) }}"
                                         alt="Foto" width="40" height="40"
                                         class="rounded border object-fit-cover"
                                         style="cursor:pointer;"
                                         data-bs-toggle="modal"
                                         data-bs-target="#imgModal"
                                         data-src="{{ asset('storage/'.$log->gambar_path) }}"
                                         data-info="{{ $log->kadet?->nama_lengkap ?? 'RFID: '.$log->rfid_uid }} — {{ $log->waktu_akses?->format('H:i:s') }}">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="pe-3 text-muted" style="font-size:.78rem;max-width:200px;">
                                {{ Str::limit($log->keterangan, 60) ?? '-' }}
                            </td>
                            <td class="text-center pe-3">
                                @if($isUnread)
                                    <button class="btn btn-sm btn-outline-success btn-mark-read"
                                            data-id="{{ $log->id }}"
                                            title="Tandai dibaca">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                @else
                                    <i class="bi bi-check2-all text-success" title="Sudah dibaca"></i>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="bi bi-check-circle-fill text-success fs-2 d-block mb-2"></i>
                                Tidak ada akses gagal hari ini
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($warnings->hasPages())
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
            <span class="text-muted" style="font-size:.8rem;">
                {{ $warnings->firstItem() }}–{{ $warnings->lastItem() }} dari {{ $warnings->total() }}
            </span>
            {{ $warnings->links() }}
        </div>
    @endif
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

@endsection

@push('scripts')
<script>
    const markReadUrl  = '{{ url("penjaga/warnings") }}';
    const markAllUrl   = '{{ route("penjaga.warnings.read-all") }}';
    const csrfToken    = '{{ csrf_token() }}';

    // ---------- Tandai satu peringatan dibaca ----------
    document.querySelectorAll('.btn-mark-read').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id  = btn.dataset.id;
            const row = btn.closest('tr');
            try {
                const res  = await fetch(`${markReadUrl}/${id}/read`, {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    // Visual: hilangkan highlight, fade opacity
                    row.className = 'warning-row';
                    row.style.opacity = '0.65';
                    // Ganti tombol jadi centang
                    btn.parentElement.innerHTML = '<i class="bi bi-check2-all text-success" title="Sudah dibaca"></i>';
                    // Hilangkan dot merah
                    const dot = row.querySelector('td:first-child span[title="Belum dibaca"]');
                    if (dot) dot.remove();
                    // Update badge sidebar & counter tombol
                    updateUnreadUI(data.unread);
                }
            } catch (e) { console.error(e); }
        });
    });

    // ---------- Tandai semua dibaca ----------
    const btnReadAll = document.getElementById('btnReadAll');
    if (btnReadAll) {
        btnReadAll.addEventListener('click', async () => {
            try {
                const res  = await fetch(markAllUrl, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                });
                const data = await res.json();
                if (data.success) {
                    // Visual: semua baris jadi normal
                    document.querySelectorAll('tr.warning-row').forEach(row => {
                        row.className = 'warning-row';
                        row.style.opacity = '0.65';
                        const dot = row.querySelector('span[title="Belum dibaca"]');
                        if (dot) dot.remove();
                        const markBtn = row.querySelector('.btn-mark-read');
                        if (markBtn) markBtn.parentElement.innerHTML = '<i class="bi bi-check2-all text-success" title="Sudah dibaca"></i>';
                    });
                    updateUnreadUI(0);
                    btnReadAll.disabled = true;
                }
            } catch (e) { console.error(e); }
        });
    }

    // ---------- Update badge sidebar & tombol ----------
    function updateUnreadUI(count) {
        // Badge di tombol
        const badge = document.getElementById('unreadCount');
        if (badge) {
            if (count > 0) badge.textContent = count;
            else badge.remove();
        }
        // Badge sidebar (elemen dengan class badge rounded-pill di link peringatan)
        const sidebarBadge = document.querySelector('.sb-link .rounded-pill');
        if (sidebarBadge) {
            if (count > 0) sidebarBadge.textContent = count > 99 ? '99+' : count;
            else sidebarBadge.remove();
        }
        if (btnReadAll && count === 0) btnReadAll.disabled = true;
    }

    // ---------- Auto-refresh ----------
    let countdown = 30;
    const badge = document.getElementById('autoRefreshBadge');

    const timer = setInterval(() => {
        countdown--;
        if (badge) badge.innerHTML = `<i class="bi bi-arrow-clockwise me-1"></i>Refresh ${countdown}s`;
        if (countdown <= 0) {
            clearInterval(timer);
            location.reload();
        }
    }, 1000);

    document.getElementById('imgModal').addEventListener('show.bs.modal', (e) => {
        const img = e.relatedTarget;
        document.getElementById('imgModalSrc').src = img.dataset.src;
        document.getElementById('imgModalInfo').textContent = img.dataset.info || '';
    });
</script>
@endpush
