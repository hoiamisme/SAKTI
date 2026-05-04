# =============================================================================
# File   : config.py
# Fungsi : Load dan validasi seluruh konfigurasi dari file .env
#          Semua modul lain mengimpor config dari sini — zero hardcoding.
# Author : SAKTI Dev Team
# Date   : 2026-05-01
# =============================================================================

import os
import sys
from pathlib import Path

from dotenv import load_dotenv

# Tentukan path .env relatif terhadap lokasi file ini
_BASE_DIR = Path(__file__).resolve().parent
load_dotenv(_BASE_DIR / ".env")


def _require(key: str) -> str:
    """Ambil env variable; raise jika kosong/tidak ada."""
    value = os.getenv(key, "").strip()
    if not value:
        print(f"[CONFIG ERROR] Environment variable '{key}' wajib diisi di file .env", file=sys.stderr)
        sys.exit(1)
    return value


def _get(key: str, default: str = "") -> str:
    return os.getenv(key, default).strip()


def _get_int(key: str, default: int) -> int:
    try:
        return int(os.getenv(key, str(default)))
    except ValueError:
        print(f"[CONFIG ERROR] '{key}' harus berupa integer.", file=sys.stderr)
        sys.exit(1)


def _get_float(key: str, default: float) -> float:
    try:
        return float(os.getenv(key, str(default)))
    except ValueError:
        print(f"[CONFIG ERROR] '{key}' harus berupa float.", file=sys.stderr)
        sys.exit(1)


def _get_bool(key: str, default: bool = False) -> bool:
    return os.getenv(key, str(default)).strip().lower() in ("true", "1", "yes")


# =============================================================================
# Konfigurasi — diakses oleh seluruh modul
# =============================================================================

# Laravel API
LARAVEL_API_URL: str = _require("LARAVEL_API_URL").rstrip("/")
API_SECRET_KEY: str = _require("API_SECRET_KEY")
API_TIMEOUT: int = _get_int("API_TIMEOUT", 10)

# RTSP / CCTV
RTSP_URL: str = _get("RTSP_URL")
RTSP_TIMEOUT: int = _get_int("RTSP_TIMEOUT", 5)
SNAPSHOT_QUALITY: int = _get_int("SNAPSHOT_QUALITY", 95)

# Direktori simpan snapshot
SNAPSHOT_DIR: Path = _BASE_DIR / "snapshots"
SNAPSHOT_DIR.mkdir(parents=True, exist_ok=True)

# Face Recognition — DeepFace + ArcFace
# Threshold: ArcFace cosine distance (0 = identik, 1 = berbeda)
# Default 0.68 = threshold resmi ArcFace. Turunkan untuk lebih ketat.
FACE_TOLERANCE: float = _get_float("FACE_TOLERANCE", 0.68)

# Direktori foto referensi wajah kadet: face_data/images/{rfid_uid}.jpg
FACE_IMAGES_DIR: Path = _BASE_DIR / _get("FACE_IMAGES_DIR", "face_data/images")
FACE_IMAGES_DIR.mkdir(parents=True, exist_ok=True)

# RFID
RFID_PORT: str = _get("RFID_PORT", "COM3")
RFID_BAUDRATE: int = _get_int("RFID_BAUDRATE", 9600)
RFID_SIMULATION_MODE: bool = _get_bool("RFID_SIMULATION_MODE", True)

# Face Verification Service (face_service.py — Flask HTTP)
FACE_SERVICE_PORT: int = _get_int("FACE_SERVICE_PORT", 5001)

# Logging
LOG_LEVEL: str = _get("LOG_LEVEL", "INFO").upper()
LOG_FILE: Path = Path(_get("LOG_FILE", "./logs/sakti.log"))
if not LOG_FILE.is_absolute():
    LOG_FILE = _BASE_DIR / LOG_FILE
LOG_FILE.parent.mkdir(parents=True, exist_ok=True)
