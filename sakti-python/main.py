# =============================================================================
# File   : main.py
# Fungsi : Entry point SAKTI Python worker — orkestrasi penuh alur akses kadet:
#          RFID → API Lookup → Face Recognition → CCTV Snapshot → Log API
# Author : SAKTI Dev Team
# Date   : 2026-05-01
# =============================================================================

import sys
import signal
from datetime import datetime
from typing import Any, Dict, Optional

import api_client
import cctv_capture
import config
import face_recognition_module as frm
import rfid_simulator
from utils.logger import get_logger

logger = get_logger(__name__)

# =============================================================================
# Konstanta tampilan
# =============================================================================
SEPARATOR = "=" * 60
STATUS_LABELS: Dict[str, str] = {
    "granted":       "✅  AKSES DIBERIKAN",
    "denied":        "❌  AKSES DITOLAK",
    "face_mismatch": "⚠️   WAJAH TIDAK COCOK",
    "rfid_unknown":  "🚫  RFID TIDAK DIKENAL",
}


# =============================================================================
# Pilih Lokasi
# =============================================================================

def select_location() -> Optional[Dict[str, Any]]:
    """
    Tampilkan menu pilihan lokasi/pos. Lokasi diambil dari API Laravel.

    Returns:
        Dict lokasi terpilih {'id': int, 'nama_lokasi': str, ...} atau None.
    """
    print(f"\n{SEPARATOR}")
    print("  SAKTI — Sistem Akses Keamanan Terintegrasi")
    print("  KSATRIAN UNHAN RI")
    print(SEPARATOR)
    print("  Memuat daftar lokasi dari server...")

    # Gunakan endpoint kadet/1 untuk test koneksi; lokasi dari mapping di .env
    # tidak ada endpoint /locations di API, jadi pakai mapping statis dari env
    locations = _build_location_list()

    if not locations:
        logger.error("Tidak ada lokasi tersedia. Pastikan Laravel API berjalan.")
        return None

    print("\n  Pilih Pos Pemeriksaan:")
    for idx, loc in enumerate(locations, start=1):
        print(f"  [{idx}] {loc['nama_lokasi']} (ID: {loc['id']})")
    print("  [0] Keluar")
    print()

    while True:
        try:
            choice = input("  Pilihan >> ").strip()
        except (KeyboardInterrupt, EOFError):
            return None

        if choice == "0":
            return None

        if choice.isdigit():
            index = int(choice) - 1
            if 0 <= index < len(locations):
                selected = locations[index]
                logger.info("Lokasi dipilih: %s (ID: %d)", selected["nama_lokasi"], selected["id"])
                return selected

        print("  Pilihan tidak valid. Coba lagi.")


def _build_location_list() -> list:
    """
    Bangun daftar lokasi dari konfigurasi .env (RTSP_URL_POS_xx).
    Fallback ke 3 lokasi default berdasarkan schema DB sample data.
    """
    # Coba ambil dari API jika tersedia endpoint /v1/locations
    result = api_client.handle_response(
        __import__("requests").get(
            api_client.build_url("v1/locations"),
            headers=api_client.HEADERS_BASE,
            timeout=config.API_TIMEOUT,
        ),
        "get_locations",
    ) if _check_api_alive() else {"success": False}

    if result.get("success"):
        data = result.get("response_data", {})
        if isinstance(data, list):
            return data
        if isinstance(data, dict) and "data" in data:
            return data["data"]

    # Fallback: lokasi dari sample data schema
    return [
        {"id": 1, "nama_lokasi": "Gerbang Utama", "kode_lokasi": "POS_01"},
        {"id": 2, "nama_lokasi": "Asrama Blok A",  "kode_lokasi": "POS_02"},
        {"id": 3, "nama_lokasi": "Ruang Kelas",    "kode_lokasi": "POS_03"},
    ]


def _check_api_alive() -> bool:
    """Periksa apakah Laravel API dapat dijangkau."""
    try:
        import requests
        resp = requests.get(
            api_client.build_url("v1/kadets/ping"),
            headers=api_client.HEADERS_BASE,
            timeout=3,
        )
        return resp.status_code != 0
    except Exception:  # pylint: disable=broad-except
        return False


# =============================================================================
# Alur Utama Satu Siklus Akses
# =============================================================================

def process_access(location: Dict[str, Any]) -> None:
    """
    Eksekusi satu siklus penuh: RFID → Lookup → Face → CCTV → Log.
    """
    location_id: int = location["id"]
    location_name: str = location["nama_lokasi"]
    waktu_akses: str = datetime.now().isoformat()

    print(f"\n{SEPARATOR}")
    print(f"  Pos: {location_name}")
    print("  Menunggu scan RFID... (Enter kosong = kembali ke menu)")
    print(SEPARATOR)

    # ------------------------------------------------------------------
    # Step 1: Baca RFID
    # ------------------------------------------------------------------
    rfid_uid = rfid_simulator.read_rfid()
    if not rfid_uid:
        logger.info("Tidak ada UID RFID — kembali ke menu.")
        return

    print(f"\n  RFID UID : {rfid_uid}")
    logger.info("Memproses RFID UID: %s di lokasi: %s", rfid_uid, location_name)

    # ------------------------------------------------------------------
    # Step 2: Lookup Kadet di Laravel API
    # ------------------------------------------------------------------
    print("  Mencari data kadet di server...")
    kadet_result = api_client.get_kadet_by_rfid(rfid_uid)

    if not kadet_result["success"] or kadet_result["status_code"] == 404:
        _finalize(
            rfid_uid=rfid_uid,
            location_id=location_id,
            status="rfid_unknown",
            kadet_nama=None,
            keterangan=f"RFID UID '{rfid_uid}' tidak terdaftar di sistem.",
            waktu_akses=waktu_akses,
        )
        return

    if not kadet_result["success"]:
        logger.error("Gagal mendapatkan data kadet dari API: %s", kadet_result)
        print("  [ERROR] Gagal menghubungi server. Akses tidak dapat diproses.")
        return

    kadet_data: Dict[str, Any] = kadet_result.get("response_data", {})
    # Sesuaikan dengan struktur JSON response Laravel
    if "data" in kadet_data:
        kadet_data = kadet_data["data"]

    kadet_nama: str = kadet_data.get("nama_lengkap", "Tidak Diketahui")
    print(f"  Kadet    : {kadet_nama}")
    logger.info("Kadet ditemukan: %s", kadet_nama)

    # ------------------------------------------------------------------
    # Step 3 & 4: Capture & Verifikasi Wajah
    # ------------------------------------------------------------------
    print("  Mengarahkan kamera ke wajah kadet...")
    frame = frm.capture_face_from_camera()

    face_result: Dict[str, Any] = {"match": False, "confidence": 0.0, "keterangan": ""}

    if frame is None:
        logger.warning("Frame kamera tidak tersedia — melewati verifikasi wajah.")
        face_status = "face_mismatch"
        face_keterangan = "Kamera tidak tersedia untuk verifikasi wajah."
    else:
        face_result = frm.verify_face(rfid_uid, frame, config.FACE_TOLERANCE)
        face_keterangan = face_result.get("keterangan", "")

        if not face_result["match"]:
            face_status = "face_mismatch"
            confidence_pct = face_result["confidence"] * 100
            print(f"  [WAJAH]  Tidak cocok (kepercayaan: {confidence_pct:.1f}%)")
            _finalize(
                rfid_uid=rfid_uid,
                location_id=location_id,
                status=face_status,
                kadet_nama=kadet_nama,
                keterangan=f"Verifikasi wajah gagal. {face_keterangan}",
                waktu_akses=waktu_akses,
                capture_cctv=True,
            )
            return
        else:
            confidence_pct = face_result["confidence"] * 100
            print(f"  [WAJAH]  Cocok ✓ (kepercayaan: {confidence_pct:.1f}%)")

    # ------------------------------------------------------------------
    # Step 5: Cek Permission (dari data kadet yang dikembalikan API)
    # ------------------------------------------------------------------
    # Laravel sudah mengecek permission via kadet.isAllowedAt().
    # Jika response API menyertakan field 'is_allowed', gunakan itu.
    # Jika tidak, anggap granted (verifikasi wajah sudah lewat).
    is_allowed: bool = bool(kadet_data.get("is_allowed", True))
    status = "granted" if is_allowed else "denied"

    keterangan_final = (
        f"Akses diberikan. {face_keterangan}"
        if is_allowed
        else f"Kadet tidak memiliki izin akses di lokasi ini. {face_keterangan}"
    )

    _finalize(
        rfid_uid=rfid_uid,
        location_id=location_id,
        status=status,
        kadet_nama=kadet_nama,
        keterangan=keterangan_final,
        waktu_akses=waktu_akses,
        capture_cctv=True,
    )


def _finalize(
    rfid_uid: str,
    location_id: int,
    status: str,
    kadet_nama: Optional[str],
    keterangan: str,
    waktu_akses: str,
    capture_cctv: bool = False,
) -> None:
    """
    Ambil snapshot CCTV (opsional), kirim log ke API, tampilkan hasil.
    """
    # ------------------------------------------------------------------
    # Step 6: Ambil Snapshot CCTV
    # ------------------------------------------------------------------
    snapshot_path: Optional[str] = None
    if capture_cctv and config.RTSP_URL:
        print("  Mengambil snapshot CCTV...")
        snapshot_path = cctv_capture.capture_snapshot(filename_prefix=f"snap_{rfid_uid}")
        if snapshot_path:
            print(f"  Snapshot : {snapshot_path}")
        else:
            print("  Snapshot : gagal diambil (lanjut tanpa gambar)")

    # ------------------------------------------------------------------
    # Step 7: Kirim Log ke Laravel API
    # ------------------------------------------------------------------
    print("  Mengirim data ke server...")
    send_result = api_client.send_access_log(
        rfid_uid=rfid_uid,
        location_id=location_id,
        status=status,
        image_path=snapshot_path,
        keterangan=keterangan,
        waktu_akses=waktu_akses,
    )

    # ------------------------------------------------------------------
    # Step 8: Tampilkan Hasil
    # ------------------------------------------------------------------
    label = STATUS_LABELS.get(status, status.upper())
    nama_display = kadet_nama or "Tidak Dikenal"

    print(f"\n{SEPARATOR}")
    print(f"  {label}")
    print(f"  Kadet    : {nama_display}")
    print(f"  UID RFID : {rfid_uid}")
    print(f"  Waktu    : {waktu_akses}")
    print(f"  Catatan  : {keterangan}")
    if send_result["success"]:
        print("  Log      : Berhasil dikirim ke server ✓")
    else:
        print(f"  Log      : Gagal dikirim — {send_result['response_data'].get('error', 'Unknown error')}")
    print(SEPARATOR)

    logger.info(
        "Akses selesai | UID: %s | Status: %s | Log terkirim: %s",
        rfid_uid, status, send_result["success"],
    )


# =============================================================================
# Signal Handler (Ctrl+C)
# =============================================================================

def _handle_exit(signum, _frame):  # noqa: ANN001
    print(f"\n\n{SEPARATOR}")
    print("  SAKTI dihentikan. Sampai jumpa!")
    print(SEPARATOR)
    logger.info("SAKTI dihentikan oleh pengguna (signal %d).", signum)
    sys.exit(0)


# =============================================================================
# Entry Point
# =============================================================================

def main() -> None:
    signal.signal(signal.SIGINT, _handle_exit)
    signal.signal(signal.SIGTERM, _handle_exit)

    logger.info("=" * 50)
    logger.info("SAKTI Python Worker dimulai.")
    logger.info("Mode RFID: %s", "Simulasi" if config.RFID_SIMULATION_MODE else "Serial")
    logger.info("API URL  : %s", config.LARAVEL_API_URL)
    logger.info("=" * 50)

    while True:
        location = select_location()
        if location is None:
            print("\n  Keluar dari SAKTI.")
            logger.info("Pengguna memilih keluar dari menu lokasi.")
            break

        # Loop scan RFID di lokasi yang sama hingga pengguna kembali ke menu
        while True:
            try:
                process_access(location)
                # Jika read_rfid() mengembalikan None (Enter kosong),
                # process_access() langsung return tanpa error — kembali ke menu
                # Beri pengguna pilihan: lanjut di lokasi sama atau ganti
                print("\n  [L] Lanjut scan di lokasi ini  |  [M] Kembali ke menu")
                try:
                    choice = input("  >> ").strip().upper()
                except (KeyboardInterrupt, EOFError):
                    break
                if choice != "L":
                    break
            except Exception as exc:  # pylint: disable=broad-except
                logger.exception("Error tidak terduga dalam siklus akses: %s", exc)
                print(f"\n  [ERROR] Terjadi kesalahan: {exc}")
                print("  Kembali ke menu utama...")
                break


if __name__ == "__main__":
    main()
