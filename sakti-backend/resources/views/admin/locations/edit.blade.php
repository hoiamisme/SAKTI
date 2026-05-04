{{--
    File   : resources/views/admin/locations/edit.blade.php
    Fungsi : Form edit pos pemeriksaan yang sudah ada
    Author : SAKTI Dev Team
    Date   : 2026-05-01
--}}
@extends('layouts.admin')

@section('title', 'Edit Lokasi')
@section('page-title', 'Edit Lokasi')

@section('content')

<div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.locations.index') }}" class="btn btn-sm btn-outline-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h5 class="mb-0 fw-bold">Edit: {{ $location->nama_lokasi }}</h5>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-6">
        <form action="{{ route('admin.locations.update', $location) }}" method="POST">
            @csrf @method('PUT')
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
                               value="{{ old('nama_lokasi', $location->nama_lokasi) }}">
                        @error('nama_lokasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.85rem;">
                            Kode Lokasi <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="kode_lokasi"
                               class="form-control font-monospace @error('kode_lokasi') is-invalid @enderror"
                               value="{{ old('kode_lokasi', $location->kode_lokasi) }}">
                        <div class="form-text">Digunakan sebagai key RTSP_URL di .env.</div>
                        @error('kode_lokasi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold" style="font-size:.85rem;">Deskripsi</label>
                        <textarea name="deskripsi" rows="3"
                                  class="form-control @error('deskripsi') is-invalid @enderror">{{ old('deskripsi', $location->deskripsi) }}</textarea>
                        @error('deskripsi')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch"
                               name="is_active" value="1" id="isActive"
                               {{ old('is_active', $location->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="isActive" style="font-size:.85rem;">
                            Aktif
                        </label>
                    </div>
                </div>
                <div class="card-footer bg-transparent d-flex gap-2 justify-content-end">
                    <a href="{{ route('admin.locations.index') }}" class="btn btn-outline-secondary btn-sm">
                        Batal
                    </a>
                    <button type="submit" class="btn btn-sm"
                            style="background:#e94560;color:#fff;border:none;">
                        <i class="bi bi-save me-1"></i>Simpan Perubahan
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection
