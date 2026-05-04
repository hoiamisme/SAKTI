{{--
    File   : resources/views/admin/kadets/index.blade.php
    Fungsi : Tabel CRUD data kadet — list, search, hapus
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Data Kadet')
@section('page-title', 'Data Kadet')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Data Kadet</h5>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            {{ $kadets->total() }} kadet terdaftar
        </p>
    </div>
    <a href="{{ route('admin.kadets.create') }}" class="btn btn-sm"
       style="background:#e94560;color:#fff;border:none;">
        <i class="bi bi-person-plus me-1"></i>Tambah Kadet
    </a>
</div>

<div class="card">
    <div class="card-header d-flex gap-2">
        <form method="GET" action="{{ route('admin.kadets.index') }}" class="d-flex gap-2 w-100">
            <input type="text" name="q" value="{{ request('q') }}"
                   class="form-control form-control-sm" style="max-width:260px;"
                   placeholder="Cari nama, NIM, atau RFID...">
            <select name="status" class="form-select form-select-sm" style="max-width:130px;">
                <option value="">Semua Status</option>
                <option value="aktif"    {{ request('status') === 'aktif'    ? 'selected' : '' }}>Aktif</option>
                <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Non-aktif</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-search"></i>
            </button>
            @if(request()->hasAny(['q','status']))
                <a href="{{ route('admin.kadets.index') }}" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-x"></i>
                </a>
            @endif
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3" style="width:50px;">#</th>
                        <th>Kadet</th>
                        <th>NIM</th>
                        <th>Prodi / Angkatan</th>
                        <th>RFID UID</th>
                        <th>Status</th>
                        <th class="text-end pe-3">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kadets as $kadet)
                        <tr>
                            <td class="ps-3 text-muted" style="font-size:.8rem;">
                                {{ $kadets->firstItem() + $loop->index }}
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $kadet->foto_path ? asset('storage/'.$kadet->foto_path) : 'https://ui-avatars.com/api/?name='.urlencode($kadet->nama_lengkap).'&size=36&background=e94560&color=fff' }}"
                                         alt="" width="36" height="36"
                                         class="rounded-circle object-fit-cover">
                                    <div>
                                        <div class="fw-semibold" style="font-size:.875rem;">{{ $kadet->nama_lengkap }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-muted" style="font-size:.85rem;">{{ $kadet->nim }}</td>
                            <td style="font-size:.82rem;">
                                <div>{{ $kadet->prodi }}</div>
                                <div class="text-muted">Angkatan {{ $kadet->angkatan }}</div>
                            </td>
                            <td>
                                <code style="font-size:.78rem;">{{ $kadet->rfid_uid }}</code>
                            </td>
                            <td>
                                <span class="badge {{ $kadet->status === 'aktif' ? 'bg-success' : 'bg-secondary' }}"
                                      style="font-size:.72rem;">
                                    {{ ucfirst($kadet->status) }}
                                </span>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.kadets.show', $kadet) }}"
                                       class="btn btn-sm btn-outline-info" title="Detail">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.kadets.edit', $kadet) }}"
                                       class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger"
                                            title="Hapus"
                                            data-bs-toggle="modal"
                                            data-bs-target="#deleteModal"
                                            data-id="{{ $kadet->id }}"
                                            data-nama="{{ $kadet->nama_lengkap }}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">
                                <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                                Tidak ada data kadet
                                @if(request('q'))
                                    untuk kata kunci "<strong>{{ request('q') }}</strong>"
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($kadets->hasPages())
        <div class="card-footer bg-transparent border-top d-flex justify-content-between align-items-center">
            <span class="text-muted" style="font-size:.8rem;">
                Menampilkan {{ $kadets->firstItem() }}–{{ $kadets->lastItem() }}
                dari {{ $kadets->total() }} kadet
            </span>
            {{ $kadets->withQueryString()->links() }}
        </div>
    @endif
</div>

{{-- Delete Confirmation Modal --}}
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>Konfirmasi Hapus
                </h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" style="font-size:.875rem;">
                    Hapus kadet <strong id="deleteNama"></strong>? Tindakan ini tidak dapat dibatalkan.
                </p>
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
    const deleteModal = document.getElementById('deleteModal');
    deleteModal.addEventListener('show.bs.modal', (e) => {
        const btn   = e.relatedTarget;
        const id    = btn.dataset.id;
        const nama  = btn.dataset.nama;
        document.getElementById('deleteNama').textContent = nama;
        document.getElementById('deleteForm').action = `/admin/kadets/${id}`;
    });
</script>
@endpush
