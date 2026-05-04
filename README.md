# SAKTI
**Sistem Akses Ksatrian Terpadu Integratif**

> Sistem keamanan cerdas berbasis RFID + Face Recognition untuk lingkungan kampus militer UNHAN RI

---

## 🎯 Tujuan

SAKTI dirancang untuk **memodernisasi sistem keamanan akses** di Ksatrian Universitas Pertahanan RI (UNHAN RI) dengan menggantikan metode pencatatan manual yang rentan terhadap kesalahan dan penyalahgunaan. Sistem ini mewujudkan pengawasan akses yang **real-time, akurat, dan terverifikasi secara biometrik** — memastikan setiap kadet yang masuk maupun keluar area kampus tercatat dengan identitas yang telah divalidasi.

---

## 🚨 Permasalahan yang Diatasi

| Masalah Lama | Solusi SAKTI |
|---|---|
| Absensi & pencatatan akses manual (buku tamu) | Log otomatis setiap scan RFID tersimpan ke database |
| Tidak ada verifikasi identitas saat masuk | Verifikasi wajah (*face recognition*) setelah tap kartu |
| Tidak diketahui kadet sedang berada di mana | Tracking posisi realtime: di luar / dalam area / di ruangan tertentu |
| Kadet bisa masuk ruangan tanpa urutan yang benar | Aturan ketat: harus lewat gerbang dulu, tidak bisa pindah ruangan tanpa keluar dahulu |
| Tidak ada dokumentasi visual saat akses terjadi | Snapshot CCTV otomatis setiap akses, tersimpan & bisa diekspor ke PDF |
| Admin tidak bisa pantau kondisi keamanan dari jauh | Dashboard admin & penjaga berbasis web, dapat diakses dari mana saja |
| Laporan akses sulit dibuat | Fitur ekspor laporan PDF dengan filter tanggal, lokasi, dan status |

---

## ✅ Manfaat

### Untuk Institusi (UNHAN RI)
- **Keamanan berlapis** — kombinasi RFID + wajah mencegah penyalahgunaan kartu akses orang lain
- **Audit trail lengkap** — setiap kejadian terekam dengan timestamp, foto snapshot CCTV, dan status verifikasi
- **Monitoring realtime** — admin dapat melihat siapa saja yang sedang berada di dalam area ksatrian pada saat ini
- **Laporan profesional** — ekspor PDF laporan akses siap cetak untuk keperluan administratif

### Untuk Penjaga Pos
- **Antarmuka sederhana** — cukup tap kartu, sistem melakukan verifikasi otomatis
- **Notifikasi instan** — peringatan muncul langsung di dashboard saat ada akses gagal / mencurigakan
- **Tidak perlu koneksi ke server eksternal** — berjalan penuh secara lokal di jaringan kampus

### Untuk Kadet
- **Proses masuk/keluar cepat** — tap kartu + verifikasi wajah selesai dalam hitungan detik
- **Transparan** — kadet dapat mengetahui hak akses mereka ke lokasi mana saja

---

## 🏗️ Arsitektur Sistem

```
[Kartu RFID]
     │
     ▼
[Reader RFID] ──► [Laravel Backend] ──► [face_verify.py]
                        │                      │
                        │              [TensorFlow/DeepFace]
                        │
                   [MySQL Database]
                        │
              ┌─────────┴─────────┐
              ▼                   ▼
     [Dashboard Admin]   [Dashboard Penjaga]
                        │
                   [cctv_capture.py]
                        │
                [TP-Link Tapo RTSP]
```

---

## 🛠️ Teknologi

- **Backend**: Laravel 10 (PHP 8.2)
- **Database**: MySQL 8.4
- **Face Recognition**: Python 3.10 · face_recognition 1.3.0 · OpenCV
- **CCTV**: TP-Link Tapo C200 via RTSP stream
- **Frontend**: Bootstrap 5.3 · Bootstrap Icons
- **PDF Export**: barryvdh/laravel-dompdf
- **Auth**: Laravel Breeze (session-based, role: admin / penjaga)

---

## 📋 Fitur Utama

- [x] Scan RFID + verifikasi wajah realtime
- [x] Tracking kehadiran kadet (masuk/keluar area & ruangan)
- [x] Aturan akses bertingkat (gerbang → ruangan, tidak bisa loncat)
- [x] Dashboard admin & penjaga dengan polling live
- [x] Snapshot CCTV otomatis setiap akses
- [x] Laporan log akses dengan ekspor PDF (termasuk foto CCTV)
- [x] Monitoring kehadiran kadet realtime per lokasi
- [x] Peringatan akses gagal untuk penjaga
- [x] Manajemen kadet, lokasi, dan hak akses via admin panel

---

## ⚙️ Cara Menjalankan

Lihat **[SETUP.md](SETUP.md)** untuk panduan instalasi lengkap.

```bash
# Backend
cd sakti-backend
php artisan serve

# Python services
cd sakti-python
python face_verify.py   # dipanggil otomatis oleh backend
python cctv_capture.py  # dipanggil otomatis oleh backend
```

---

<div align="center">
  <sub>Dikembangkan untuk <strong>Universitas Pertahanan RI (UNHAN RI)</strong> · 2026</sub>
</div>
