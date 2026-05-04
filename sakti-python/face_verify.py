# =============================================================================
# File   : face_verify.py
# Fungsi : Script standalone verifikasi wajah — dipanggil langsung oleh PHP
#          (Symfony Process). Membaca JSON dari stdin, menulis JSON ke stdout.
#
# Backend: face_recognition (dlib) — TIDAK menggunakan TensorFlow/Keras.
#          Dlib tidak melakukan network/Winsock init, aman di subprocess
#          yang di-spawn oleh PHP di Windows.
#
# Input  stdin  : {"rfid_uid": "...", "face_image_b64": "...", "face_image_ref": "..."}
# Output stdout : {"match": bool, "confidence": float, "keterangan": str}
#
# CCTV: Snapshot TIDAK dilakukan di sini. PHP memanggil cctv_capture.py
#       secara terpisah setelah mendapat hasil face recognition.
# =============================================================================

from __future__ import annotations

import base64
import json
import os
import sys
from typing import Optional

# Simpan stdout asli SEBELUM apapun (meski dlib tidak mencemari stdout,
# tetap redirect sebagai langkah defensif)
_real_stdout = sys.stdout
sys.stdout   = sys.stderr

_DIR = os.path.dirname(os.path.abspath(__file__))
if _DIR not in sys.path:
    sys.path.insert(0, _DIR)

os.environ["PYTHONIOENCODING"] = "utf-8"

import numpy as np  # noqa: E402
import cv2          # noqa: E402


def _write_json(data: dict) -> None:
    _real_stdout.write(json.dumps(data, ensure_ascii=True))
    _real_stdout.flush()


def _fail_early(keterangan: str) -> None:
    """Hanya untuk error validasi awal (sebelum CCTV dibuka)."""
    _write_json({
        "match":        False,
        "confidence":   0.0,
        "keterangan":   keterangan,
        "snapshot_b64": "",
    })
    sys.exit(0)



def _do_face_recognition(probe_rgb, ref_rgb, tolerance):
    """
    Jalankan face recognition dlib. Return (match, confidence, keterangan).
    Tidak memanggil sys.exit -- error dikembalikan sebagai tuple.
    """
    try:
        import face_recognition as fr
    except ImportError:
        return False, 0.0, "Library 'face_recognition' tidak terinstall."

    probe_locs = fr.face_locations(probe_rgb, model="hog")
    if not probe_locs:
        return False, 0.0, "Tidak ada wajah terdeteksi di kamera. Pastikan wajah terlihat jelas."

    ref_locs = fr.face_locations(ref_rgb, model="hog")
    if not ref_locs:
        return False, 0.0, "Tidak ada wajah terdeteksi di foto referensi. Daftarkan ulang wajah kadet."

    probe_encs = fr.face_encodings(probe_rgb, probe_locs)
    ref_encs   = fr.face_encodings(ref_rgb,   ref_locs)

    if not probe_encs:
        return False, 0.0, "Gagal menghitung encoding wajah dari kamera."
    if not ref_encs:
        return False, 0.0, "Gagal menghitung encoding wajah referensi."

    distance = float(fr.face_distance([ref_encs[0]], probe_encs[0])[0])
    matched  = bool(distance <= tolerance)
    conf     = round(max(0.0, min(1.0, 1.0 - distance / (tolerance * 2))), 4)

    if matched:
        ket = u"Wajah cocok \u2014 confidence: {:.1f}%.".format(conf * 100)
    else:
        ket = u"Wajah tidak cocok \u2014 confidence: {:.1f}% (distance: {:.4f}, threshold: {}).".format(
            conf * 100, distance, tolerance
        )
    return matched, conf, ket


def main():
    # ------------------------------------------------------------------
    # 1. Baca & validasi input JSON dari stdin
    # ------------------------------------------------------------------
    try:
        raw  = sys.stdin.read()
        data = json.loads(raw)
    except (json.JSONDecodeError, ValueError) as exc:
        _fail_early("Input tidak valid: {}".format(exc))
        return

    rfid_uid = str(data.get("rfid_uid", "")).strip().upper()
    face_b64 = str(data.get("face_image_b64", "")).strip()
    face_ref = str(data.get("face_image_ref") or "").strip()

    if not rfid_uid:
        _fail_early("Field 'rfid_uid' wajib diisi.")
        return
    if not face_b64:
        _fail_early("Field 'face_image_b64' wajib diisi.")
        return
    if not face_ref:
        _fail_early(
            "Foto referensi wajah tidak ditemukan di database. "
            "Daftarkan wajah kadet melalui halaman admin."
        )
        return

    # ------------------------------------------------------------------
    # 2. Load konfigurasi
    # ------------------------------------------------------------------
    tolerance = 0.55
    try:
        import config as _cfg
        tolerance = float(_cfg.FACE_TOLERANCE)
    except Exception:
        pass

    # ------------------------------------------------------------------
    # 3. Decode gambar probe (dari webcam scan) --> RGB numpy
    # ------------------------------------------------------------------
    probe_rgb  = None
    match      = False
    confidence = 0.0
    keterangan = ""

    try:
        if "," in face_b64:
            face_b64 = face_b64.split(",", 1)[1]
        probe_bytes = base64.b64decode(face_b64)
        probe_arr   = np.frombuffer(probe_bytes, dtype=np.uint8)
        probe_bgr   = cv2.imdecode(probe_arr, cv2.IMREAD_COLOR)
        if probe_bgr is None or probe_bgr.size == 0:
            keterangan = "Gambar dari kamera tidak dapat di-decode."
        else:
            probe_rgb = cv2.cvtColor(probe_bgr, cv2.COLOR_BGR2RGB)
    except Exception as exc:
        keterangan = "Error decode gambar kamera: {}".format(exc)

    # ------------------------------------------------------------------
    # 4. Decode gambar referensi (dari DB via PHP)
    # ------------------------------------------------------------------
    ref_rgb = None
    if not keterangan:
        try:
            ref_bytes = base64.b64decode(face_ref)
            ref_arr   = np.frombuffer(ref_bytes, dtype=np.uint8)
            ref_bgr   = cv2.imdecode(ref_arr, cv2.IMREAD_COLOR)
            if ref_bgr is None or ref_bgr.size == 0:
                keterangan = "Foto referensi dari database tidak dapat di-decode."
            else:
                ref_rgb = cv2.cvtColor(ref_bgr, cv2.COLOR_BGR2RGB)
        except Exception as exc:
            keterangan = "Error decode foto referensi: {}".format(exc)

    # ------------------------------------------------------------------
    # 5. Face recognition (hanya jika decode berhasil)
    # ------------------------------------------------------------------
    if not keterangan and probe_rgb is not None and ref_rgb is not None:
        match, confidence, keterangan = _do_face_recognition(
            probe_rgb, ref_rgb, tolerance
        )

    # ------------------------------------------------------------------
    # 6. Tulis output JSON (snapshot dilakukan terpisah oleh cctv_capture.py)
    # ------------------------------------------------------------------
    _write_json({
        "match":      match,
        "confidence": confidence,
        "keterangan": keterangan,
    })


if __name__ == "__main__":
    main()