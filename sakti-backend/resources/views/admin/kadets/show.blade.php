{{--
    File   : resources/views/admin/kadets/show.blade.php
    Fungsi : Halaman detail satu kadet — foto, data, riwayat akses, hak akses
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Detail Kadet — ' . $kadet->nama_lengkap)
@section('page-title', 'Detail Kadet')

@section('content')

<div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.kadets.index') }}" class="btn btn-sm btn-outline-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">{{ $kadet->nama_lengkap }}</h5>
</div>

<div class="row g-3">
    {{-- Profile card --}}
    <div class="col-12 col-md-4 col-xl-3">
        <div class="card text-center">
            <div class="card-body py-4">
                <img src="{{ $kadet->foto_path
                             ? asset('storage/'.$kadet->foto_path)
                             : 'https://ui-avatars.com/api/?name='.urlencode($kadet->nama_lengkap).'&size=100&background=e94560&color=fff' }}"
                     alt="Foto" width="90" height="90"
                     class="rounded-circle object-fit-cover border border-3 border-light shadow mb-3">

                <h6 class="fw-bold mb-0">{{ $kadet->nama_lengkap }}</h6>
                <div class="text-muted mb-2" style="font-size:.82rem;">{{ $kadet->nim }}</div>

                <span class="badge {{ $kadet->status === 'aktif' ? 'bg-success' : 'bg-secondary' }} mb-3">
                    {{ ucfirst($kadet->status) }}
                </span>

                <div class="text-start border-top pt-3" style="font-size:.82rem;">
                    <div class="mb-2">
                        <i class="bi bi-mortarboard text-muted me-2"></i>
                        <span>{{ $kadet->prodi }}</span>
                    </div>
                    <div class="mb-2">
                        <i class="bi bi-calendar-event text-muted me-2"></i>
                        <span>Angkatan {{ $kadet->angkatan }}</span>
                    </div>
                    <div>
                        <i class="bi bi-credit-card text-muted me-2"></i>
                        <code style="font-size:.8rem;">{{ $kadet->rfid_uid }}</code>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-top-0 d-flex gap-2 pb-3 px-3">
                <a href="{{ route('admin.kadets.edit', $kadet) }}"
                   class="btn btn-sm btn-warning w-50">
                    <i class="bi bi-pencil me-1"></i>Edit
                </a>
                <button type="button"
                        class="btn btn-sm btn-outline-danger w-50"
                        data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="bi bi-trash me-1"></i>Hapus
                </button>
            </div>
        </div>

        {{-- Hak akses --}}
        <div class="card mt-3">
            <div class="card-header">
                <i class="bi bi-key me-2 text-muted"></i>Hak Akses Lokasi
            </div>
            <div class="card-body p-0">
                @forelse($kadet->accessPermissions as $perm)
                    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
                        <span style="font-size:.82rem;">{{ $perm->location->nama_lokasi ?? '-' }}</span>
                        <span class="badge {{ $perm->is_allowed ? 'bg-success' : 'bg-danger' }}"
                              style="font-size:.7rem;">
                            {{ $perm->is_allowed ? 'Diizinkan' : 'Diblokir' }}
                        </span>
                    </div>
                @empty
                    <div class="text-center text-muted py-3" style="font-size:.82rem;">
                        Belum ada hak akses
                    </div>
                @endforelse
            </div>
            @if($kadet->accessPermissions->isNotEmpty())
                <div class="card-footer bg-transparent">
                    <a href="{{ route('admin.permissions.index') }}" class="btn btn-sm btn-outline-secondary w-100">
                        <i class="bi bi-pencil-square me-1"></i>Kelola Hak Akses
                    </a>
                </div>
            @endif
        </div>
    </div>

    {{-- Log akses terbaru --}}
    <div class="col-12 col-md-8 col-xl-9">
        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-clock-history me-2 text-muted"></i>Riwayat Akses (10 Terakhir)</span>
                <a href="{{ route('admin.reports.index', ['kadet_id' => $kadet->id]) }}"
                   class="btn btn-sm btn-outline-secondary" style="font-size:.75rem;">
                    Semua Riwayat
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Waktu</th>
                                <th>Lokasi</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                                <th class="pe-3">Foto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kadet->accessLogs as $log)
                                <tr>
                                    <td class="ps-3 text-nowrap" style="font-size:.8rem;">
                                        {{ $log->waktu_akses?->format('d/m/Y H:i') ?? '-' }}
                                    </td>
                                    <td style="font-size:.82rem;">
                                        {{ $log->location->nama_lokasi ?? '-' }}
                                    </td>
                                    <td>
                                        @php
                                            $bc = match($log->status) {
                                                'granted'       => 'bg-success',
                                                'denied'        => 'bg-danger',
                                                'face_mismatch' => 'bg-warning text-dark',
                                                default         => 'bg-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $bc }}" style="font-size:.72rem;">
                                            {{ ucfirst(str_replace('_', ' ', $log->status)) }}
                                        </span>
                                    </td>
                                    <td class="text-muted" style="font-size:.78rem;max-width:200px;">
                                        {{ Str::limit($log->keterangan, 60) ?? '-' }}
                                    </td>
                                    <td class="pe-3">
                                        @if($log->gambar_path)
                                            <img src="{{ asset('storage/'.$log->gambar_path) }}"
                                                 alt="Snapshot" width="40" height="40"
                                                 class="rounded object-fit-cover border cursor-pointer"
                                                 style="cursor:pointer;"
                                                 data-bs-toggle="modal" data-bs-target="#imgModal"
                                                 data-src="{{ asset('storage/'.$log->gambar_path) }}"
                                                 data-waktu="{{ $log->waktu_akses?->format('d/m/Y H:i') }}">
                                        @else
                                            <span class="text-muted" style="font-size:.78rem;">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        Belum ada riwayat akses
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Snapshot modal --}}
<div class="modal fade" id="imgModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
            <div class="modal-header py-2">
                <small class="modal-title text-muted" id="imgModalWaktu"></small>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0 text-center">
                <img id="imgModalSrc" src="" alt="Snapshot" class="img-fluid w-100"
                     style="border-radius:0 0 .375rem .375rem;">
            </div>
        </div>
    </div>
</div>

{{-- Delete modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" style="font-size:.875rem;">
                Hapus <strong>{{ $kadet->nama_lengkap }}</strong>? Tindakan ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('admin.kadets.destroy', $kadet) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.getElementById('imgModal').addEventListener('show.bs.modal', (e) => {
        const img = e.relatedTarget;
        document.getElementById('imgModalSrc').src   = img.dataset.src;
        document.getElementById('imgModalWaktu').textContent = img.dataset.waktu;
    });
</script>
@endpush
