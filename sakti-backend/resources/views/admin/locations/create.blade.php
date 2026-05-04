{{--
    File   : resources/views/admin/locations/create.blade.php
    Fungsi : Form tambah pos pemeriksaan baru
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Tambah Lokasi')
@section('page-title', 'Tambah Lokasi')

@section('content')

<div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.locations.index') }}" class="btn btn-sm btn-outline-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Tambah Pos Pemeriksaan</h5>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <form action="{{ route('admin.locations.store') }}" method="POST">
            @csrf
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-geo-alt me-2 text-muted"></i>Detail Lokasi
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.85rem;">
                            Nama Lokasi <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="nama_lokasi"
                               class="form-control @error('nama_lokasi') is-invalid @enderror"
                               value="{{ old('nama_lokasi') }}"
                               placeholder="Gerbang Utama">
                        @error('nama_lokasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.85rem;">
                            Kode Lokasi <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="kode_lokasi"
                               class="form-control font-monospace @error('kode_lokasi') is-invalid @enderror"
                               value="{{ old('kode_lokasi') }}"
                               placeholder="POS_01">
                        <div class="form-text">Format: POS_01, POS_02, dst. Harus unik.</div>
                        @error('kode_lokasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.85rem;">Deskripsi</label>
                        <textarea name="deskripsi" rows="3"
                                  class="form-control @error('deskripsi') is-invalid @enderror"
                                  placeholder="Keterangan singkat tentang lokasi ini...">{{ old('deskripsi') }}</textarea>
                        @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="is_active" value="1" id="isActive"
                               {{ old('is_active', '1') ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive" style="font-size:.85rem;">
                            Aktif (tersedia untuk akses)
                        </label>
                    </div>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary btn-sm">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-sm"
                            style="background:#e94560;color:#fff;border:none;">
                        <i class="bi bi-save me-1"></i>Simpan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
