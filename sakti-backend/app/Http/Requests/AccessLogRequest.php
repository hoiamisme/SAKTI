<?php
// =============================================================================
// File   : app/Http/Requests/AccessLogRequest.php
// Fungsi : Form Request untuk validasi data akses yang dikirim dari Python
// Author : SAKTI Dev Team
// Date   : 2026-05-01
// =============================================================================

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccessLogRequest extends FormRequest
{
    /**
     * Semua request ke endpoint API ini dianggap authorized.
     * Otorisasi sesungguhnya dilakukan via API key di controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rfid_uid'    => ['required', 'string', 'max:50'],
            'location_id' => ['required', 'integer', 'exists:locations,id'],
            'status'      => ['required', 'string', 'in:granted,denied,face_mismatch,rfid_unknown'],
            'image'       => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
            'keterangan'  => ['nullable', 'string', 'max:1000'],
            'waktu_akses' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'rfid_uid.required'    => 'RFID UID wajib diisi.',
            'rfid_uid.max'         => 'RFID UID maksimal 50 karakter.',
            'location_id.required' => 'Location ID wajib diisi.',
            'location_id.exists'   => 'Location ID tidak ditemukan dalam database.',
            'status.required'      => 'Status akses wajib diisi.',
            'status.in'            => 'Status harus salah satu dari: granted, denied, face_mismatch, rfid_unknown.',
            'image.mimes'          => 'File gambar harus berformat jpg atau png.',
            'image.max'            => 'Ukuran gambar maksimal 2 MB.',
        ];
    }
}
