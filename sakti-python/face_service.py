# =============================================================================
# File   : face_service.py
# Fungsi : Flask HTTP microservice untuk verifikasi wajah via web.
#          Menerima foto wajah (base64 JPEG) + RFID UID dari Laravel,
#          menjalankan DeepFace + ArcFace, mengembalikan JSON hasil verifikasi.
#          Model DeepFace tetap loaded di memori — tidak reload per request.
#
# Jalankan: python face_service.py
#           (aktifkan .venv terlebih dahulu)
#
# Endpoint:
#   GET  /health            → { ok: true, model: "ArcFace" }
#   POST /verify            → { match, confidence, keterangan }
#     Body JSON: { rfid_uid: str, face_image_b64: str }
#
# Author : SAKTI Dev Team
# Date   : 2026-05-03
# =============================================================================

from __future__ import annotations

import base64
import logging
import os
import sys
from typing import Any, Dict, Tuple

import numpy as np

import config
import face_recognition_module as frm
from utils.logger import get_logger

# ---------------------------------------------------------------------------
# Flask — import wajib sebelum service berjalan
# ---------------------------------------------------------------------------
try:
    from flask import Flask, jsonify, request as flask_request
except ImportError:
    print(
        "[FATAL] Library 'flask' belum terinstall.\n"
        "        Jalankan: pip install flask",
        file=sys.stderr,
    )
    sys.exit(1)

# ---------------------------------------------------------------------------
# OpenCV — wajib untuk decode base64 → numpy frame
# ---------------------------------------------------------------------------
try:
    import cv2  # type: ignore
except ImportError:
    print(
        "[FATAL] Library 'opencv-python' belum terinstall.\n"
        "        Jalankan: pip install opencv-python",
        file=sys.stderr,
    )
    sys.exit(1)

logger = get_logger(__name__)

# ---------------------------------------------------------------------------
# Flask app
# ---------------------------------------------------------------------------
app = Flask(__name__)

# Sembunyikan log HTTP default Flask (agar tidak bising)
log = logging.getLogger("werkzeug")
log.setLevel(logging.WARNING)


# =============================================================================
# Helper: decode base64 → numpy BGR frame
# =============================================================================

def _b64_to_frame(face_b64: str) -> Tuple[Any, str]:
    """
    Decode base64 JPEG/PNG string menjadi numpy ndarray BGR.

    Returns:
        (frame, error_msg) — error_msg kosong jika berhasil.
    """
    try:
        # Hapus header data-URL jika ada (misal "data:image/jpeg;base64,...")
        if "," in face_b64:
            face_b64 = face_b64.split(",", 1)[1]

        img_bytes = base64.b64decode(face_b64)
        nparr = np.frombuffer(img_bytes, dtype=np.uint8)
        frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)  # pylint: disable=no-member

        if frame is None or frame.size == 0:
            return None, "Gambar tidak dapat di-decode (frame kosong)."

        return frame, ""
    except (base64.binascii.Error, ValueError) as exc:
        return None, f"Base64 tidak valid: {exc}"
    except Exception as exc:  # pylint: disable=broad-exception-caught
        return None, f"Error decode gambar: {exc}"


# =============================================================================
# Routes
# =============================================================================

@app.route("/health", methods=["GET"])
def health():
    """Cek layanan berjalan."""
    return jsonify({"ok": True, "model": "ArcFace", "service": "SAKTI Face Service"}), 200


@app.route("/verify", methods=["POST"])
def verify():
    """
    Verifikasi wajah kadet.

    Request JSON:
        rfid_uid      (str, wajib) : UID RFID kadet
        face_image_b64 (str, wajib) : Gambar wajah langsung dari webcam (base64 JPEG)

    Response JSON:
        match       (bool)  : True jika wajah cocok
        confidence  (float) : Skor 0.0 – 1.0
        keterangan  (str)   : Pesan deskriptif
    """
    data: Dict[str, Any] = flask_request.get_json(silent=True) or {}

    rfid_uid: str = str(data.get("rfid_uid", "")).strip().upper()
    face_b64: str = str(data.get("face_image_b64", "")).strip()

    # ------------------------------------------------------------------
    # Validasi input
    # ------------------------------------------------------------------
    if not rfid_uid:
        return jsonify({"error": "Field 'rfid_uid' wajib diisi."}), 400
    if not face_b64:
        return jsonify({"error": "Field 'face_image_b64' wajib diisi."}), 400

    # ------------------------------------------------------------------
    # Decode gambar → numpy frame
    # ------------------------------------------------------------------
    frame, decode_err = _b64_to_frame(face_b64)
    if frame is None:
        logger.warning("[VERIFY] Gagal decode gambar untuk UID '%s': %s", rfid_uid, decode_err)
        return jsonify({
            "match": False,
            "confidence": 0.0,
            "keterangan": f"Gambar tidak valid: {decode_err}",
        }), 200

    # ------------------------------------------------------------------
    # Verifikasi wajah via face_recognition_module
    # ------------------------------------------------------------------
    logger.info("[VERIFY] Mulai verifikasi UID: %s", rfid_uid)
    result = frm.verify_face(rfid_uid, frame, config.FACE_TOLERANCE)

    logger.info(
        "[VERIFY] UID: %s | match: %s | confidence: %.1f%%",
        rfid_uid,
        result.get("match"),
        result.get("confidence", 0.0) * 100,
    )

    return jsonify(result), 200


# =============================================================================
# Entry Point
# =============================================================================

if __name__ == "__main__":
    port: int = getattr(config, "FACE_SERVICE_PORT", 5001)
    host: str = "127.0.0.1"  # Hanya lokal — jangan expose ke publik

    print("=" * 60)
    print("  SAKTI Face Verification Service")
    print(f"  URL     : http://{host}:{port}")
    print(f"  Model   : ArcFace (DeepFace {config.FACE_TOLERANCE} threshold)")
    print("  Tekan Ctrl+C untuk berhenti.")
    print("=" * 60)

    logger.info(
        "Face service dimulai di http://%s:%d | tolerance=%.2f",
        host, port, config.FACE_TOLERANCE,
    )

    app.run(host=host, port=port, debug=False, use_reloader=False)
