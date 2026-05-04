{{--
    File   : resources/views/admin/locations/index.blade.php
    Fungsi : CRUD pos pemeriksaan/lokasi — tambah, edit, aktifkan, hapus
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Kelola Lokasi')
@section('page-title', 'Kelola Lokasi')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Pos Pemeriksaan</h5>
        <p class="text-muted mb-0" style="font-size:.82rem;">{{ $locations->count() }} lokasi terdaftar</p>
    </div>
    <a href="{{ route('admin.locations.create') }}" class="btn btn-sm"
       style="background:#e94560;color:#fff;border:none;">
        <i class="bi bi-plus-circle me-1"></i>Tambah Lokasi
    </a>
</div>

<div class="row g-3">
    @forelse($locations as $loc)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-2 mb-1">
                                <span class="badge bg-dark font-monospace">{{ $loc->kode_lokasi }}</span>
                                <span class="badge {{ $loc->is_active ? 'bg-success' : 'bg-secondary' }}"
                                      style="font-size:.7rem;">
                                    {{ $loc->is_active ? 'Aktif' : 'Non-aktif' }}
                                </span>
                            </div>
                            <h6 class="fw-bold mb-1">{{ $loc->nama_lokasi }}</h6>
                            @if($loc->deskripsi)
                                <p class="text-muted mb-2" style="font-size:.8rem;">{{ $loc->deskripsi }}</p>
                            @endif
                            <div class="d-flex align-items-center gap-1 text-muted" style="font-size:.78rem;">
                                <i class="bi bi-list-check"></i>
                                <span>{{ $loc->access_logs_count }} log akses</span>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <div class="rounded" style="width:48px;height:48px;background:linear-gradient(135deg,#1a1a2e,#0f3460);display:flex;align-items:center;justify-content:center;">
                                <i class="bi bi-geo-alt-fill text-white fs-5"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-top d-flex gap-2">
                    <a href="{{ route('admin.locations.edit', $loc) }}"
                       class="btn btn-sm btn-outline-warning flex-grow-1">
                        <i class="bi bi-pencil me-1"></i>Edit
                    </a>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteModal"
                            data-id="{{ $loc->id }}"
                            data-nama="{{ $loc->nama_lokasi }}">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-geo fs-2 d-block mb-2"></i>
                    Belum ada lokasi terdaftar
                </div>
            </div>
        </div>
    @endforelse
</div>

{{-- Delete Modal --}}
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
                Hapus lokasi <strong id="deleteNama"></strong>?
                Data log akses terkait mungkin terpengaruh.
            </div>
            <div class="modal-footer border-0 pt-0">
                <button class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteForm" method="POST">
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
    document.getElementById('deleteModal').addEventListener('show.bs.modal', (e) => {
        const btn = e.relatedTarget;
        document.getElementById('deleteNama').textContent = btn.dataset.nama;
        document.getElementById('deleteForm').action = `/admin/locations/${btn.dataset.id}`;
    });
</script>
@endpush
