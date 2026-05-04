# =============================================================================
# File   : face_recognition_module.py
# Fungsi : Verifikasi wajah kadet menggunakan DeepFace + ArcFace.
#          Foto referensi diambil dari database Laravel via API (face_image),
#          di-cache lokal di face_data/images/{rfid_uid}.jpg.
#          Fallback: cari file lokal jika API tidak tersedia.
# Author : SAKTI Dev Team
# Date   : 2026-05-01
# =============================================================================

import base64
import os
import tempfile
from pathlib import Path
from typing import TYPE_CHECKING, Any, Dict, Optional

import requests as _requests

import config
from utils.logger import get_logger

logger = get_logger(__name__)

# cv2 — optional dependency.
if TYPE_CHECKING:
    import cv2  # pragma: no cover
else:
    try:
        import cv2
        _CV2_AVAILABLE: bool = True
    except ImportError:
        cv2 = None  # type: ignore[assignment]
        _CV2_AVAILABLE = False

_CV2_AVAILABLE = cv2 is not None

# Ekstensi foto referensi yang didukung (cache lokal)
_SUPPORTED_EXTS = (".jpg", ".jpeg", ".png")

# Header API
_API_HEADERS = {
    "X-API-KEY": config.API_SECRET_KEY,
    "Accept": "application/json",
}


# =============================================================================
# Referensi foto kadet — DB-first, fallback ke file lokal
# =============================================================================

def get_reference_image_path(rfid_uid: str) -> Optional[Path]:
    """
    Dapatkan path foto referensi wajah kadet.
    Urutan prioritas:
      1. Unduh dari Laravel API (face_image base64 di DB) → cache lokal
      2. File lokal di FACE_IMAGES_DIR/{rfid_uid}.jpg/.jpeg/.png

    Returns:
        Path file lokal yang siap dipakai DeepFace, atau None.
    """
    # Pastikan direktori cache ada
    cache_dir: Path = config.FACE_IMAGES_DIR
    cache_dir.mkdir(parents=True, exist_ok=True)
    cache_path = cache_dir / f"{rfid_uid}.jpg"

    # 1. Coba ambil dari API jika konfigurasi tersedia
    if config.LARAVEL_API_URL and config.API_SECRET_KEY:
        api_path = _fetch_face_from_api(rfid_uid, cache_path)
        if api_path:
            return api_path

    # 2. Fallback: cek file lokal yang sudah ada (dari upload manual atau cache)
    for ext in _SUPPORTED_EXTS:
        candidate = cache_dir / f"{rfid_uid}{ext}"
        if candidate.exists():
            logger.debug("Menggunakan foto referensi lokal: %s", candidate)
            return candidate

    logger.warning(
        "Foto referensi tidak ditemukan untuk UID '%s'. "
        "Daftarkan wajah kadet melalui halaman admin.",
        rfid_uid,
    )
    return None


def _fetch_face_from_api(rfid_uid: str, cache_path: Path) -> Optional[Path]:
    """
    Ambil face_image (base64 JPEG) dari Laravel API, simpan ke cache_path.
    Endpoint: GET /admin/kadets/{id}/face-image  (mencari via rfid_uid)
    Sebenarnya, endpoint lookup kadet by rfid adalah /v1/kadets/{rfid_uid},
    lalu ambil face_image-nya dari response.
    """
    try:
        url = f"{config.LARAVEL_API_URL.rstrip('/')}/api/v1/kadets/{rfid_uid}"
        resp = _requests.get(url, headers=_API_HEADERS, timeout=config.API_TIMEOUT)
        if resp.status_code != 200:
            return None

        data = resp.json()
        # response: { success: true, data: { face_image: "base64...", ... } }
        kadet_data = data.get("data") or data
        face_b64: Optional[str] = kadet_data.get("face_image")

        if not face_b64:
            logger.debug("API: kadet '%s' belum punya face_image di database.", rfid_uid)
            return None

        # Decode dan simpan ke cache
        img_bytes = base64.b64decode(face_b64)
        cache_path.write_bytes(img_bytes)
        logger.info("Foto wajah '%s' diunduh dari DB dan di-cache ke: %s", rfid_uid, cache_path)
        return cache_path

    except (_requests.exceptions.ConnectionError, _requests.exceptions.Timeout):
        logger.warning("API tidak tersedia saat mengambil foto wajah '%s' — pakai cache lokal.", rfid_uid)
        return None
    except Exception as exc:  # pylint: disable=broad-exception-caught
        logger.error("Error mengambil face_image dari API untuk '%s': %s", rfid_uid, exc)
        return None


def has_reference_image(rfid_uid: str) -> bool:
    """Cek cepat apakah foto referensi tersedia (lokal atau DB)."""
    # Cek lokal dulu (cepat)
    for ext in _SUPPORTED_EXTS:
        if (config.FACE_IMAGES_DIR / f"{rfid_uid}{ext}").exists():
            return True
    # Kalau tidak ada lokal, coba API
    cache_path = config.FACE_IMAGES_DIR / f"{rfid_uid}.jpg"
    return _fetch_face_from_api(rfid_uid, cache_path) is not None


# =============================================================================
# Capture frame dari webcam / RTSP
# =============================================================================

def capture_face_from_camera(
    camera_source: Any = 0,
    max_attempts: int = 3,
) -> Optional[Any]:
    """
    Ambil satu frame dari webcam atau stream RTSP.

    Args:
        camera_source: Indeks integer (0 = webcam default) atau URL RTSP.
        max_attempts:  Jumlah percobaan jika frame kosong.

    Returns:
        numpy.ndarray (BGR) atau None jika gagal.
    """
    if not _CV2_AVAILABLE or cv2 is None:
        logger.error(
            "Library 'opencv-python' tidak terinstall. Jalankan: pip install opencv-python"
        )
        return None

    cap = None
    try:
        cap = cv2.VideoCapture(camera_source)  # pylint: disable=no-member
        cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)  # pylint: disable=no-member

        if not cap.isOpened():
            logger.error("Tidak dapat membuka sumber kamera: %s", camera_source)
            return None

        for attempt in range(1, max_attempts + 1):
            ret, frame = cap.read()
            if ret and frame is not None and frame.size > 0:
                logger.debug("Frame berhasil diambil (percobaan %d).", attempt)
                return frame
            logger.warning(
                "Frame kosong dari kamera (percobaan %d/%d).", attempt, max_attempts
            )

        logger.error("Gagal mendapatkan frame setelah %d percobaan.", max_attempts)
        return None

    except Exception as exc:
        logger.error("Error saat capture kamera: %s", exc)
        return None

    finally:
        if cap is not None:
            cap.release()


# =============================================================================
# Verifikasi wajah — face_recognition (dlib ResNet)
# TIDAK menggunakan TensorFlow/Keras — aman di subprocess Windows (no Winsock)
# =============================================================================

def verify_face(
    rfid_uid: str,
    frame: Any,
    tolerance: Optional[float] = None,
) -> Dict[str, Any]:
    """
    Bandingkan wajah pada frame dengan foto referensi kadet menggunakan
    face_recognition (dlib ResNet-34, akurasi ~99.38% pada dataset LFW).

    Args:
        rfid_uid:  UID RFID kadet.
        frame:     numpy.ndarray BGR dari kamera (OpenCV format).
        tolerance: Threshold Euclidean distance dlib (default: 0.55).
                   Turunkan untuk lebih ketat (misal 0.5).

    Returns:
        Dict:
            - match (bool)       : True jika wajah cocok.
            - confidence (float) : Skor 0.0–1.0.
            - keterangan (str)   : Pesan deskriptif.
    """
    threshold = tolerance if tolerance is not None else config.FACE_TOLERANCE
    result: Dict[str, Any] = {
        "match": False,
        "confidence": 0.0,
        "keterangan": "",
    }

    if frame is None:
        result["keterangan"] = "Frame kamera tidak tersedia."
        return result

    ref_path = get_reference_image_path(rfid_uid)
    if ref_path is None:
        result["keterangan"] = (
            f"Foto referensi tidak ditemukan untuk UID '{rfid_uid}'. "
            "Rekam wajah kadet melalui halaman admin (Tambah/Edit Kadet)."
        )
        return result

    try:
        import face_recognition as fr  # type: ignore  — dlib, no TF
    except ImportError as exc:
        logger.error("Library 'face_recognition' belum terinstall: %s", exc)
        result["keterangan"] = f"Library tidak tersedia: {exc}"
        return result

    if not _CV2_AVAILABLE or cv2 is None:
        result["keterangan"] = "Library 'opencv-python' tidak terinstall."
        return result

    try:
        # Konversi frame probe BGR → RGB (face_recognition expects RGB)
        probe_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)  # pylint: disable=no-member

        # Load foto referensi
        ref_bgr = cv2.imread(str(ref_path))  # pylint: disable=no-member
        if ref_bgr is None:
            logger.error("Gagal baca foto referensi: %s", ref_path)
            result["keterangan"] = "Gagal membaca foto referensi."
            return result
        ref_rgb = cv2.cvtColor(ref_bgr, cv2.COLOR_BGR2RGB)  # pylint: disable=no-member

        # Deteksi wajah
        probe_locs = fr.face_locations(probe_rgb, model="hog")
        ref_locs   = fr.face_locations(ref_rgb,   model="hog")

        if not probe_locs:
            result["keterangan"] = "Tidak ada wajah terdeteksi pada gambar dari kamera. Pastikan wajah terlihat jelas."
            return result
        if not ref_locs:
            result["keterangan"] = "Tidak ada wajah terdeteksi pada foto referensi. Daftarkan ulang wajah kadet."
            return result

        # Hitung 128-dimensi face embedding
        probe_encs = fr.face_encodings(probe_rgb, probe_locs)
        ref_encs   = fr.face_encodings(ref_rgb,   ref_locs)

        if not probe_encs or not ref_encs:
            result["keterangan"] = "Gagal menghitung encoding wajah."
            return result

        distance: float  = float(fr.face_distance([ref_encs[0]], probe_encs[0])[0])
        verified: bool   = bool(distance <= threshold)
        confidence: float = round(max(0.0, min(1.0, 1.0 - (distance / (threshold * 2)))), 4)

        result.update({
            "match": verified,
            "confidence": confidence,
            "keterangan": (
                f"Wajah cocok \u2014 confidence: {confidence * 100:.1f}%."
                if verified
                else f"Wajah tidak cocok \u2014 confidence: {confidence * 100:.1f}% "
                     f"(distance: {distance:.4f}, threshold: {threshold})."
            ),
        })

        logger.info(
            "[FACE] UID %s — dlib distance: %.4f | threshold: %.2f | "
            "match: %s | confidence: %.1f%%",
            rfid_uid, distance, threshold, verified, confidence * 100,
        )

    except Exception as exc:
        logger.error("Error verifikasi wajah UID '%s': %s", rfid_uid, exc)
        result["keterangan"] = f"Error verifikasi wajah: {exc}"

    return result
