{{--
    File   : resources/views/admin/permissions/index.blade.php
    Fungsi : Matrix hak akses kadet × lokasi — toggle switch via AJAX
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Hak Akses')
@section('page-title', 'Kelola Hak Akses')

@push('styles')
<style>
    .matrix-table th, .matrix-table td {
        text-align: center; vertical-align: middle;
        min-width: 100px; padding: .5rem .4rem;
    }
    .matrix-table th:first-child, .matrix-table td:first-child {
        text-align: left; min-width: 180px; padding-left: 1rem;
    }
    .form-switch .form-check-input { cursor: pointer; width: 2.5em; height: 1.25em; }
    .form-switch .form-check-input:checked { background-color: #198754; border-color: #198754; }
    .saving-spinner { display: none; }
    .toggle-cell.saving .saving-spinner { display: inline-block; }
    .toggle-cell.saving .form-check-input { pointer-events: none; opacity: .6; }

    .kadet-row:hover { background: rgba(233,69,96,.03); }

    .loc-header {
        background: #1a1a2e; color: #fff;
        font-size: .75rem; font-weight: 600;
        letter-spacing: .5px;
    }
    .matrix-container { overflow-x: auto; }
</style>
@endpush

@section('content')

<div class="d-flex align-items-center justify-content-between mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Matrix Hak Akses</h5>
        <p class="text-muted mb-0" style="font-size:.82rem;">
            {{ $kadets->count() }} kadet · {{ $locations->count() }} lokasi
        </p>
    </div>
    <div class="d-flex gap-2">
        <button id="btnGrantAll" class="btn btn-sm btn-success" title="Izinkan semua akses">
            <i class="bi bi-check-all me-1"></i>Izinkan Semua
        </button>
        <button id="btnRevokeAll" class="btn btn-sm btn-outline-danger" title="Cabut semua akses">
            <i class="bi bi-x-circle me-1"></i>Cabut Semua
        </button>
    </div>
</div>

{{-- Legend --}}
<div class="d-flex gap-3 mb-3" style="font-size:.8rem;">
    <span><i class="bi bi-toggle-on text-success fs-5 me-1"></i>Diizinkan</span>
    <span><i class="bi bi-toggle-off text-secondary fs-5 me-1"></i>Diblokir</span>
    <span id="saveStatus" class="text-muted ms-auto"></span>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="matrix-container">
            @if($kadets->isEmpty() || $locations->isEmpty())
                <div class="text-center text-muted py-5">
                    <i class="bi bi-exclamation-circle fs-2 d-block mb-2"></i>
                    @if($kadets->isEmpty())
                        Belum ada kadet aktif. <a href="{{ route('admin.kadets.create') }}">Tambah kadet</a> terlebih dahulu.
                    @else
                        Belum ada lokasi aktif. <a href="{{ route('admin.locations.create') }}">Tambah lokasi</a> terlebih dahulu.
                    @endif
                </div>
            @else
                <table class="table matrix-table mb-0">
                    <thead>
                        <tr>
                            <th class="loc-header ps-3">Kadet / Lokasi</th>
                            @foreach($locations as $loc)
                                <th class="loc-header">
                                    <div>{{ $loc->nama_lokasi }}</div>
                                    <div class="text-muted fw-normal" style="font-size:.7rem;">{{ $loc->kode_lokasi }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($kadets as $kadet)
                            <tr class="kadet-row">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="{{ $kadet->foto_path
                                                    ? asset('storage/'.$kadet->foto_path)
                                                    : 'https://ui-avatars.com/api/?name='.urlencode($kadet->nama_lengkap).'&size=30&background=e94560&color=fff' }}"
                                             alt="" width="30" height="30"
                                             class="rounded-circle object-fit-cover flex-shrink-0">
                                        <div>
                                            <div class="fw-semibold" style="font-size:.82rem;">{{ $kadet->nama_lengkap }}</div>
                                            <div class="text-muted" style="font-size:.72rem;">{{ $kadet->nim }}</div>
                                        </div>
                                    </div>
                                </td>

                                @foreach($locations as $loc)
                                    @php
                                        $key       = "{$kadet->id}_{$loc->id}";
                                        $isAllowed = $permMap->get($key, false);
                                        $checkId   = "toggle_{$kadet->id}_{$loc->id}";
                                    @endphp
                                    <td class="toggle-cell" id="cell_{{ $checkId }}">
                                        <div class="form-check form-switch d-inline-flex align-items-center gap-1 mb-0">
                                            <input class="form-check-input permission-toggle"
                                                   type="checkbox"
                                                   role="switch"
                                                   id="{{ $checkId }}"
                                                   data-kadet="{{ $kadet->id }}"
                                                   data-location="{{ $loc->id }}"
                                                   {{ $isAllowed ? 'checked' : '' }}>
                                            <span class="saving-spinner spinner-border spinner-border-sm text-secondary"
                                                  role="status"></span>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    const csrfToken  = document.querySelector('meta[name="csrf-token"]').content;
    const storeUrl   = '{{ route("admin.permissions.store") }}';
    const saveStatus = document.getElementById('saveStatus');

    let saveTimer;

    function setStatus(msg, cls = 'text-muted') {
        if (saveStatus) {
            saveStatus.className = cls;
            saveStatus.textContent = msg;
        }
    }

    /**
     * POST ke admin/permissions dengan updateOrCreate semantics.
     */
    async function togglePermission(kadetId, locationId, isAllowed, cellEl) {
        if (cellEl) cellEl.classList.add('saving');
        setStatus('Menyimpan...', 'text-warning');

        const body = new URLSearchParams({
            _token:      csrfToken,
            kadet_id:    kadetId,
            location_id: locationId,
            is_allowed:  isAllowed ? '1' : '0',
        });

        try {
            const resp = await fetch(storeUrl, {
                method:  'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                body:    body.toString(),
            });

            if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

            clearTimeout(saveTimer);
            setStatus('✓ Tersimpan', 'text-success');
            saveTimer = setTimeout(() => setStatus(''), 2500);
        } catch (err) {
            console.error('Toggle permission error:', err);
            setStatus('✗ Gagal menyimpan', 'text-danger');
            // Revert toggle on failure
            const chk = document.getElementById(`toggle_${kadetId}_${locationId}`);
            if (chk) chk.checked = !isAllowed;
        } finally {
            if (cellEl) cellEl.classList.remove('saving');
        }
    }

    // Attach change events to all toggles
    document.querySelectorAll('.permission-toggle').forEach((chk) => {
        chk.addEventListener('change', function () {
            const cell = document.getElementById(`cell_toggle_${this.dataset.kadet}_${this.dataset.location}`);
            togglePermission(
                this.dataset.kadet,
                this.dataset.location,
                this.checked,
                cell
            );
        });
    });

    // Grant all
    const btnGrantAll = document.getElementById('btnGrantAll');
    if (btnGrantAll) {
        btnGrantAll.addEventListener('click', async () => {
            if (!confirm('Izinkan SEMUA kadet untuk SEMUA lokasi?')) return;
            const toggles = document.querySelectorAll('.permission-toggle:not(:checked)');
            for (const chk of toggles) {
                chk.checked = true;
                await togglePermission(chk.dataset.kadet, chk.dataset.location, true, null);
            }
        });
    }

    // Revoke all
    const btnRevokeAll = document.getElementById('btnRevokeAll');
    if (btnRevokeAll) {
        btnRevokeAll.addEventListener('click', async () => {
            if (!confirm('Cabut SEMUA hak akses? Kadet tidak akan bisa masuk ke lokasi manapun.')) return;
            const toggles = document.querySelectorAll('.permission-toggle:checked');
            for (const chk of toggles) {
                chk.checked = false;
                await togglePermission(chk.dataset.kadet, chk.dataset.location, false, null);
            }
        });
    }
}());
</script>
@endpush
