{{--
    File   : resources/views/penjaga/kadets/index.blade.php
    Fungsi : Data kadet read-only untuk penjaga pos — lihat daftar dan detail dasar
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.penjaga')

@section('title', 'Data Kadet')
@section('page-title', 'Data Kadet')

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Data Kadet</h5>
        <p class="text-muted mb-0" style="font-size:.82rem;">{{ $kadets->total() }} kadet terdaftar</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <form method="GET" action="{{ route('penjaga.kadets.index') }}" class="d-flex gap-2">
            <input type="text" name="q" value="{{ request('q') }}"
                   class="form-control form-control-sm" style="max-width:260px;"
                   placeholder="Cari nama, NIM...">
            <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-search"></i>
            </button>
            @if(request('q'))
                <a href="{{ route('penjaga.kadets.index') }}" class="btn btn-sm btn-outline-danger">
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
                        <th class="ps-3">Kadet</th>
                        <th>NIM</th>
                        <th>Prodi / Angkatan</th>
                        <th>RFID UID</th>
                        <th class="pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($kadets as $kadet)
                        <tr data-bs-toggle="modal"
                            data-bs-target="#kadetModal"
                            data-id="{{ $kadet->id }}"
                            data-nama="{{ $kadet->nama_lengkap }}"
                            data-nim="{{ $kadet->nim }}"
                            data-prodi="{{ $kadet->prodi }}"
                            data-angkatan="{{ $kadet->angkatan }}"
                            data-rfid="{{ $kadet->rfid_uid }}"
                            data-status="{{ $kadet->status }}"
                            data-foto="{{ $kadet->foto_path ? asset('storage/'.$kadet->foto_path) : 'https://ui-avatars.com/api/?name='.urlencode($kadet->nama_lengkap).'&size=80&background=e94560&color=fff' }}"
                            style="cursor:pointer;">
                            <td class="ps-3">
                                <div class="d-flex align-items-center gap-2">
                                    <img src="{{ $kadet->foto_path
                                                ? asset('storage/'.$kadet->foto_path)
                                                : 'https://ui-avatars.com/api/?name='.urlencode($kadet->nama_lengkap).'&size=36&background=e94560&color=fff' }}"
                                         alt="" width="36" height="36"
                                         class="rounded-circle object-fit-cover">
                                    <span class="fw-semibold" style="font-size:.875rem;">{{ $kadet->nama_lengkap }}</span>
                                </div>
                            </td>
                            <td class="text-muted" style="font-size:.85rem;">{{ $kadet->nim }}</td>
                            <td style="font-size:.82rem;">
                                {{ $kadet->prodi }} / {{ $kadet->angkatan }}
                            </td>
                            <td><code style="font-size:.78rem;">{{ $kadet->rfid_uid }}</code></td>
                            <td class="pe-3">
                                <span class="badge {{ $kadet->status === 'aktif' ? 'bg-success' : 'bg-secondary' }}"
                                      style="font-size:.72rem;">
                                    {{ ucfirst($kadet->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-5">
                                <i class="bi bi-person-x fs-2 d-block mb-2"></i>
                                Tidak ada kadet ditemukan
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($kadets->hasPages())
        <div class="card-footer bg-transparent d-flex justify-content-between align-items-center">
            <span class="text-muted" style="font-size:.8rem;">
                {{ $kadets->firstItem() }}–{{ $kadets->lastItem() }} dari {{ $kadets->total() }}
            </span>
            {{ $kadets->withQueryString()->links() }}
        </div>
    @endif
</div>

{{-- Kadet Detail Modal (read-only) --}}
<div class="modal fade" id="kadetModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title">Detail Kadet</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <img id="modalFoto" src="" alt="Foto" width="70" height="70"
                         class="rounded-circle object-fit-cover border">
                    <div>
                        <div class="fw-bold fs-6" id="modalNama"></div>
                        <div class="text-muted" style="font-size:.82rem;" id="modalNim"></div>
                        <span class="badge mt-1" id="modalStatus"></span>
                    </div>
                </div>
                <div class="row g-2" style="font-size:.85rem;">
                    <div class="col-5 text-muted">Program Studi</div>
                    <div class="col-7" id="modalProdi"></div>
                    <div class="col-5 text-muted">Angkatan</div>
                    <div class="col-7" id="modalAngkatan"></div>
                    <div class="col-5 text-muted">RFID UID</div>
                    <div class="col-7"><code id="modalRfid" style="font-size:.82rem;"></code></div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    document.getElementById('kadetModal').addEventListener('show.bs.modal', (e) => {
        const row = e.relatedTarget;
        document.getElementById('modalFoto').src       = row.dataset.foto;
        document.getElementById('modalNama').textContent    = row.dataset.nama;
        document.getElementById('modalNim').textContent     = row.dataset.nim;
        document.getElementById('modalProdi').textContent   = row.dataset.prodi;
        document.getElementById('modalAngkatan').textContent = row.dataset.angkatan;
        document.getElementById('modalRfid').textContent    = row.dataset.rfid;
        const statusEl = document.getElementById('modalStatus');
        statusEl.textContent = row.dataset.status.charAt(0).toUpperCase() + row.dataset.status.slice(1);
        statusEl.className   = 'badge mt-1 ' + (row.dataset.status === 'aktif' ? 'bg-success' : 'bg-secondary');
    });
</script>
@endpush
