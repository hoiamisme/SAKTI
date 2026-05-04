# =============================================================================
# File   : api_client.py
# Fungsi : Komunikasi dengan Laravel API — kirim log akses (multipart/form-data)
#          dan lookup data kadet berdasarkan RFID UID.
# Author : SAKTI Dev Team
# Date   : 2026-05-01
# =============================================================================

import os
from pathlib import Path
from typing import Any, Dict, Optional

import requests

import config
from utils.logger import get_logger

logger = get_logger(__name__)

HEADERS_BASE: Dict[str, str] = {
    "X-API-KEY": config.API_SECRET_KEY,
    "Accept": "application/json",
}
# backward-compat alias
_HEADERS_BASE = HEADERS_BASE


# =============================================================================
# Helper
# =============================================================================

def build_url(path: str) -> str:
    """Gabungkan base URL API dengan path endpoint."""
    return f"{config.LARAVEL_API_URL}/{path.lstrip('/')}"


# backward-compat alias
_build_url = build_url


def handle_response(response: requests.Response, context: str) -> Dict[str, Any]:
    """
    Proses response HTTP. Return dict {success, response_data, status_code}.
    """
    try:
        data = response.json()
    except ValueError:
        data = {"raw": response.text}

    if response.status_code in (200, 201):
        logger.info("[%s] Sukses — HTTP %d", context, response.status_code)
        return {"success": True, "response_data": data, "status_code": response.status_code}

    logger.warning(
        "[%s] HTTP %d — %s", context, response.status_code, str(data)[:200]
    )
    return {"success": False, "response_data": data, "status_code": response.status_code}


# backward-compat alias
_handle_response = handle_response


# =============================================================================
# Public API
# =============================================================================

def get_kadet_by_rfid(rfid_uid: str) -> Dict[str, Any]:
    """
    GET /api/v1/kadets/{rfid_uid} — ambil data kadet dari Laravel.

    Returns:
        Dict dengan key:
            - success (bool)
            - response_data (dict): data kadet atau pesan error
            - status_code (int)
    """
    url = _build_url(f"v1/kadets/{rfid_uid}")
    logger.info("GET kadet untuk RFID: %s", rfid_uid)

    try:
        response = requests.get(
            url,
            headers=_HEADERS_BASE,
            timeout=config.API_TIMEOUT,
        )
        return _handle_response(response, "get_kadet_by_rfid")

    except requests.exceptions.Timeout:
        logger.error("Timeout saat GET kadet (UID: %s) — %ds", rfid_uid, config.API_TIMEOUT)
        return {"success": False, "response_data": {"error": "Request timeout"}, "status_code": 0}

    except requests.exceptions.ConnectionError as exc:
        logger.error("Koneksi ke Laravel API gagal: %s", exc)
        return {"success": False, "response_data": {"error": "Connection error"}, "status_code": 0}

    except Exception as exc:  # pylint: disable=broad-exception-caught
        logger.error("Error tidak terduga saat GET kadet: %s", exc)
        return {"success": False, "response_data": {"error": str(exc)}, "status_code": 0}


def send_access_log(
    rfid_uid: str,
    location_id: int,
    status: str,
    image_path: Optional[str] = None,
    keterangan: Optional[str] = None,
    waktu_akses: Optional[str] = None,
) -> Dict[str, Any]:
    """
    POST /api/v1/access-log — kirim data akses kadet ke Laravel.

    Data dikirim sebagai multipart/form-data agar file gambar ikut terkirim.

    Args:
        rfid_uid:    UID RFID kadet.
        location_id: ID lokasi/pos pemeriksaan.
        status:      Salah satu dari: granted, denied, face_mismatch, rfid_unknown.
        image_path:  Path lokal file snapshot (opsional).
        keterangan:  Catatan tambahan (opsional).
        waktu_akses: Timestamp ISO 8601 (opsional, default server time).

    Returns:
        Dict dengan key: success (bool), response_data (dict), status_code (int).
    """
    url = _build_url("v1/access-log")

    form_data: Dict[str, Any] = {
        "rfid_uid": rfid_uid,
        "location_id": str(location_id),
        "status": status,
    }
    if keterangan:
        form_data["keterangan"] = keterangan
    if waktu_akses:
        form_data["waktu_akses"] = waktu_akses

    logger.info(
        "POST access-log | UID: %s | Lokasi: %d | Status: %s | Gambar: %s",
        rfid_uid, location_id, status, image_path or "tidak ada",
    )

    file_handle = None
    try:
        files = None
        if image_path and os.path.isfile(image_path):
            file_handle = open(image_path, "rb")  # noqa: WPS515
            files = {"image": (Path(image_path).name, file_handle, "image/jpeg")}
        elif image_path:
            logger.warning("File gambar tidak ditemukan: %s — dikirim tanpa gambar.", image_path)

        response = requests.post(
            url,
            headers=_HEADERS_BASE,
            data=form_data,
            files=files,
            timeout=config.API_TIMEOUT,
        )
        return _handle_response(response, "send_access_log")

    except requests.exceptions.Timeout:
        logger.error("Timeout saat POST access-log — %ds", config.API_TIMEOUT)
        return {"success": False, "response_data": {"error": "Request timeout"}, "status_code": 0}

    except requests.exceptions.ConnectionError as exc:
        logger.error("Koneksi ke Laravel API gagal: %s", exc)
        return {"success": False, "response_data": {"error": "Connection error"}, "status_code": 0}

    except Exception as exc:  # pylint: disable=broad-exception-caught
        logger.error("Error tidak terduga saat POST access-log: %s", exc)
        return {"success": False, "response_data": {"error": str(exc)}, "status_code": 0}

    finally:
        if file_handle is not None:
            file_handle.close()
