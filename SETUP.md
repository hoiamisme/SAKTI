# SAKTI — Sistem Akses Keamanan Terintegrasi
## Panduan Setup & Integrasi

**Author :** SAKTI Dev Team  
**Date   :** 2026-05-01  
**Stack  :** Laravel 10 · MySQL 8.4 · Python 3.10 · Bootstrap 5.3  

---

## A. Langkah Instalasi

### 1. Setup MySQL di Laragon — Import Schema

Pastikan Laragon sudah berjalan. Buka terminal di Laragon atau gunakan HeidiSQL / phpMyAdmin.

```bash
# Masuk ke MySQL (Laragon default: root, no password)
mysql -u root

# Buat database
CREATE DATABASE sakti_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sakti_db;

# Import schema
SOURCE C:/SAKTI/sakti-backend/database/sakti_schema.sql;

# Verifikasi tabel
SHOW TABLES;
# Expected: access_logs, access_permissions, kadets, locations, users
```

---

### 2. Setup Laravel — Composer Install, .env, Key Generate

```bash
cd C:\SAKTI\sakti-backend

# Install dependensi PHP
composer install --no-dev --optimize-autoloader

# Salin file environment
copy .env.example .env

# Generate application key
php artisan key:generate

# Edit .env — sesuaikan nilai berikut:
# DB_DATABASE=sakti_db
# DB_USERNAME=root
# DB_PASSWORD=
# API_SECRET_KEY=sakti-secret-2026   ← sama dengan Python .env

# Jalankan migrasi (jika menggunakan migration, SKIP jika sudah import schema SQL)
# php artisan migrate

# Seed data awal (user admin + penjaga contoh)
php artisan db:seed
```

**Isi minimal `.env` Laravel:**

```dotenv
APP_NAME="SAKTI"
APP_ENV=production
APP_KEY=                        # diisi otomatis oleh key:generate
APP_DEBUG=false
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sakti_db
DB_USERNAME=root
DB_PASSWORD=

FILESYSTEM_DISK=public

# Kunci rahasia — harus sama persis dengan Python .env
API_SECRET_KEY=sakti-secret-2026
```

---

### 3. Storage Link

```bash
cd C:\SAKTI\sakti-backend

# Buat symlink public/storage → storage/app/public
php artisan storage:link

# Buat folder snapshot agar Python bisa menulis
mkdir -p storage\app\public\snapshots

# Verifikasi
ls public\storage
```

---

### 4. Setup Python — pip install

```bash
cd C:\SAKTI\sakti-python

# (Opsional tapi disarankan) Buat virtual environment
python -m venv .venv
.venv\Scripts\activate        # Windows
# source .venv/bin/activate   # Linux/macOS

# Install dependensi
pip install -r requirements.txt

# Verifikasi instalasi face_recognition
python -c "import face_recognition; print('face_recognition OK')"
```

> **Catatan Windows:** `face_recognition` membutuhkan `dlib`. Jika gagal install:
> ```bash
> pip install cmake
> pip install dlib
> pip install face_recognition
> ```
> Atau gunakan wheel prebuilt: https://github.com/sachadee/Dlib

---

### 5. Isi Semua .env (Laravel & Python)

**Python `.env`** (`C:\SAKTI\sakti-python\.env`):

```dotenv
# API Laravel
API_BASE_URL=http://localhost/sakti-backend/public
API_SECRET_KEY=sakti-secret-2026

# Kamera RTSP (Tapo C200 default format)
RTSP_URL=rtsp://admin:password@192.168.1.100:554/stream1

# RFID Serial Port
RFID_PORT=COM3           # Windows: COM3, COM4, dll.
RFID_BAUDRATE=9600

# Snapshot output
SNAPSHOT_DIR=snapshots

# Mode debug (true = verbose log)
DEBUG=false
```

> Pastikan `API_SECRET_KEY` di Laravel `.env` dan Python `.env` **identik**.

---

### 6. Test Koneksi RTSP

```bash
# Install ffmpeg/ffplay jika belum ada
# Download: https://ffmpeg.org/download.html

# Test stream kamera Tapo C200
ffplay rtsp://admin:PASSWORD@192.168.1.100:554/stream1

# Jika muncul jendela video → koneksi berhasil
# Ctrl+C untuk keluar

# Alternatif test dengan Python (tanpa ffplay)
python -c "
import cv2
cap = cv2.VideoCapture('rtsp://admin:PASSWORD@192.168.1.100:554/stream1')
print('RTSP OK' if cap.isOpened() else 'RTSP GAGAL')
cap.release()
"
```

---

## B. requirements.txt Python

```
face_recognition==1.3.0
opencv-python==4.8.0
requests==2.31.0
python-dotenv==1.0.0
pyserial==3.5
numpy==1.24.0
Pillow==10.0.0
```

File lengkap: [`sakti-python/requirements.txt`](sakti-python/requirements.txt)

---

## C. Alur End-to-End — Contoh Output Terminal

Jalankan sistem Python:

```bash
cd C:\SAKTI\sakti-python
python main.py
```

**Output terminal lengkap:**

```
============================================================
  SAKTI — Sistem Akses Keamanan Terintegrasi
  KSATRIAN UNHAN RI | v1.0.0
============================================================

[CONFIG] API      : http://localhost/sakti-backend/public
[CONFIG] RTSP     : rtsp://admin:***@192.168.1.100:554/stream1
[CONFIG] RFID     : COM3 @ 9600 baud
[CONFIG] Snapshot : snapshots/

[INIT] Menghubungkan ke serial port COM3...
[INIT] Serial OK — COM3 @ 9600 baud

[SAKTI] Pilih lokasi:
  1. Pos Utama
  2. Lab Komputer
  3. Lab MIPA
> 1

[SAKTI] Lokasi: Pos Utama | Menunggu RFID...

[RFID] UID terdeteksi: A1B2C3D4
[API] GET /api/v1/kadets/A1B2C3D4 → 200 OK
[KADET] Ditemukan: Taruna Ahmad Fauzi | Prodi: TI | Angkatan: 2022

[CAMERA] Membuka webcam (RTSP)...
[FACE] Memproses frame...
[FACE] Hasil: MATCH | Confidence: 94.3%

[PERMISSION] Cek akses Pos Utama → GRANTED

[CCTV] Connecting rtsp://admin:***@192.168.1.100:554/stream1...
[CCTV] Snapshot berhasil: snapshots/2026/05/20260501_143022_A1B2C3D4.jpg

[API] POST /api/v1/access-log → 201 Created
[SAKTI] ✓ AKSES DIBERIKAN — Ahmad Fauzi | Pos Utama | 14:30:22

[SAKTI] Menunggu RFID berikutnya...

------------------------------------------------------------

[RFID] UID terdeteksi: FF001122
[API] GET /api/v1/kadets/FF001122 → 404 Not Found
[SAKTI] ✗ RFID TIDAK DIKENAL — FF001122

[CCTV] Snapshot berhasil: snapshots/2026/05/20260501_143155_FF001122.jpg
[API] POST /api/v1/access-log → 201 Created
[SAKTI] ✗ AKSES DITOLAK — RFID tidak terdaftar | Pos Utama | 14:31:55

[SAKTI] Menunggu RFID berikutnya...

------------------------------------------------------------

[RFID] UID terdeteksi: B3C4D5E6
[API] GET /api/v1/kadets/B3C4D5E6 → 200 OK
[KADET] Ditemukan: Taruna Siti Rahayu | Prodi: SI | Angkatan: 2023

[CAMERA] Membuka webcam (RTSP)...
[FACE] Memproses frame...
[FACE] Hasil: MISMATCH | Confidence: 41.2%

[CCTV] Snapshot berhasil: snapshots/2026/05/20260501_143302_B3C4D5E6.jpg
[API] POST /api/v1/access-log → 201 Created
[SAKTI] ✗ WAJAH TIDAK COCOK — Siti Rahayu | Pos Utama | 14:33:02

[SAKTI] Menunggu RFID berikutnya...
```

---

## D. Troubleshooting

| Masalah | Penyebab | Solusi |
|---|---|---|
| `RTSP tidak connect` / `cv2 error` | IP kamera salah atau format URL keliru | Cek IP dengan `ping 192.168.1.100`. Format Tapo C200: `rtsp://admin:PASS@IP:554/stream1` |
| `face_recognition lambat` | Resolusi capture terlalu tinggi | Turunkan resolusi di `config.py`: `CAPTURE_WIDTH=320`, `CAPTURE_HEIGHT=240` |
| `API 401 Unauthorized` | `API_SECRET_KEY` tidak cocok | Pastikan nilai `API_SECRET_KEY` di `.env` Laravel **dan** Python identik (case-sensitive) |
| `Storage permission error` | Folder `storage/` tidak writable | Linux/macOS: `chmod -R 775 storage bootstrap/cache` dan `chown -R www-data:www-data storage` |
| `php artisan storage:link` gagal | Symlink sudah ada | Hapus dulu: `rm public/storage` lalu jalankan ulang |
| `dlib install failed` (Windows) | Tidak ada CMake/Visual C++ | Install [CMake](https://cmake.org/download/) + [Build Tools VS 2022](https://visualstudio.microsoft.com/visual-cpp-build-tools/) lalu `pip install dlib` |
| `COM port not found` | Port RFID reader salah | Cek Device Manager → Ports. Ganti `RFID_PORT` di `.env` Python |
| `403 Forbidden` di frontend | Storage symlink tidak aktif atau `APP_URL` salah | Cek `APP_URL` di `.env` dan jalankan `php artisan storage:link` |
| Login redirect ke `/home` | `LoginController` belum diupdate | Pastikan `app/Http/Controllers/Auth/LoginController.php` sudah menggunakan `redirectTo()` method (bukan `$redirectTo` property) |

---

## E. Struktur Folder Akhir

```
C:\SAKTI\
├── SETUP.md                        ← panduan ini
│
├── sakti-backend\                  ← Laravel 10
│   ├── app\Http\Controllers\
│   │   ├── Admin\
│   │   │   ├── DashboardController.php
│   │   │   ├── KadetController.php
│   │   │   ├── LocationController.php
│   │   │   ├── PermissionController.php
│   │   │   └── ReportController.php
│   │   ├── Api\
│   │   │   └── AccessLogController.php
│   │   ├── Auth\
│   │   │   └── LoginController.php
│   │   └── Penjaga\
│   │       ├── DashboardController.php
│   │       ├── KadetController.php
│   │       ├── ScanController.php
│   │       └── WarningController.php
│   ├── database\
│   │   └── sakti_schema.sql
│   ├── resources\views\
│   │   ├── admin\
│   │   │   ├── dashboard.blade.php
│   │   │   ├── kadets\{index,create,edit,show}.blade.php
│   │   │   ├── locations\{index,create,edit}.blade.php
│   │   │   ├── permissions\index.blade.php
│   │   │   └── reports\index.blade.php
│   │   ├── auth\login.blade.php
│   │   ├── layouts\{admin,penjaga}.blade.php
│   │   └── penjaga\
│   │       ├── dashboard.blade.php
│   │       ├── scan.blade.php
│   │       ├── kadets\index.blade.php
│   │       └── warnings\index.blade.php
│   └── routes\
│       ├── api.php
│       └── web.php
│
└── sakti-python\                   ← Python 3.10
    ├── .env
    ├── config.py
    ├── main.py
    ├── api_client.py
    ├── rfid_simulator.py
    ├── face_recognition_module.py
    ├── cctv_capture.py
    ├── requirements.txt
    └── utils\
        └── logger.py
```

---

## F. Menjalankan Sistem (Quick Start)

```bash
# Terminal 1 — Laravel dev server
cd C:\SAKTI\sakti-backend
php artisan serve
# → http://127.0.0.1:8000

# Terminal 2 — Python SAKTI
cd C:\SAKTI\sakti-python
.venv\Scripts\activate
python main.py
```

Buka browser: **http://127.0.0.1:8000**  
Login default (setelah seeder):
- Admin  : `admin@sakti.id` / `password`
- Penjaga: `penjaga@sakti.id` / `password`
