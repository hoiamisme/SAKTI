# =============================================================================
# File   : cctv_capture.py
# Fungsi : Capture satu frame dari RTSP CCTV, tambah overlay info,
#          return base64 JPEG ke stdout.
#
# Dipanggil oleh PHP (ScanController) sebagai subprocess terpisah dari
# face_verify.py, sehingga CCTV bekerja independen dari face recognition.
#
# Input  stdin  : {"rfid_uid": "...", "status": "granted|face_mismatch|..."}
# Output stdout : {"snapshot_b64": "...", "ok": true|false}
# =============================================================================
from __future__ import annotations

import base64
import json
import os
import sys

_real_stdout = sys.stdout
sys.stdout   = sys.stderr

_DIR = os.path.dirname(os.path.abspath(__file__))
if _DIR not in sys.path:
    sys.path.insert(0, _DIR)

os.environ["PYTHONIOENCODING"] = "utf-8"

# ---------------------------------------------------------------------------
# Fix #1 — Force Winsock initialization BEFORE loading cv2/FFmpeg DLL.
# Saat PHP web server spawn subprocess Python, Windows Winsock LSP kadang
# gagal inisialisasi (WSAEPROVIDERFAILEDINIT -10106) ketika FFmpeg DLL coba
# membuka socket pertama kali.  Dengan membuat socket di sini lebih dulu,
# Python memanggil WSAStartup() dan semua LSP ter-register sebelum cv2 load.
# ---------------------------------------------------------------------------
if sys.platform == "win32":
    import socket as _wsa_init
    import ctypes as _ct
    try:
        # Explicit WSAStartup via ctypes (belt + suspenders)
        _wsa_data = _ct.create_string_buffer(408)
        _ct.windll.ws2_32.WSAStartup(0x0202, _wsa_data)  # type: ignore[attr-defined]
    except Exception:
        pass
    try:
        # Actually create & close a real socket to exercise full LSP stack
        _s = _wsa_init.socket(_wsa_init.AF_INET, _wsa_init.SOCK_STREAM)
        _s.close()
    except Exception:
        pass
    del _wsa_init, _ct

# ---------------------------------------------------------------------------
# Fix #2 — Set OPENCV_FFMPEG_CAPTURE_OPTIONS BEFORE importing cv2 so the
# FFmpeg backend uses explicit TCP transport and a real socket timeout.
# ---------------------------------------------------------------------------
os.environ.setdefault(
    "OPENCV_FFMPEG_CAPTURE_OPTIONS",
    "rtsp_transport;tcp|stimeout;10000000",   # 10 s socket timeout
)

import cv2       # noqa: E402
import numpy as np  # noqa: E402


def _out(data):
    _real_stdout.write(json.dumps(data, ensure_ascii=True))
    _real_stdout.flush()


def main():
    # ------------------------------------------------------------------
    # Baca input JSON dari stdin
    # ------------------------------------------------------------------
    try:
        data      = json.loads(sys.stdin.read())
        rfid_uid  = str(data.get("rfid_uid", "")).strip().upper() or "UNKNOWN"
        status    = str(data.get("status", "unknown")).strip()
    except Exception as exc:
        _out({"ok": False, "snapshot_b64": "", "error": str(exc)})
        return

    # ------------------------------------------------------------------
    # Load konfigurasi RTSP
    # ------------------------------------------------------------------
    rtsp_url = ""
    quality  = 82
    try:
        import config as _cfg
        rtsp_url = str(_cfg.RTSP_URL).strip()
        quality  = int(getattr(_cfg, "SNAPSHOT_QUALITY", 82))
    except Exception:
        pass

    if not rtsp_url:
        _out({"ok": False, "snapshot_b64": "", "error": "RTSP_URL tidak dikonfigurasi."})
        return

    # ------------------------------------------------------------------
    # Buka RTSP dan capture frame dengan retry
    # ------------------------------------------------------------------
    cap = None
    try:
        # Fix #3 — set timeout SEBELUM open() agar ffmpeg memakai nilai ini
        # saat membangun koneksi TCP (bukan sesudahnya yang tidak berpengaruh).
        cap = cv2.VideoCapture()
        cap.set(cv2.CAP_PROP_OPEN_TIMEOUT_MSEC, 8000)
        cap.set(cv2.CAP_PROP_READ_TIMEOUT_MSEC, 8000)
        cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)
        cap.open(rtsp_url, cv2.CAP_FFMPEG)

        if not cap.isOpened():
            _out({"ok": False, "snapshot_b64": "", "error": "Tidak bisa membuka RTSP stream."})
            return

        # Flush buffer lama; coba baca frame dengan retry
        ret, frame = False, None
        for attempt in range(6):
            cap.grab()
            ret, frame = cap.read()
            if ret and frame is not None:
                break

        if not ret or frame is None:
            _out({"ok": False, "snapshot_b64": "", "error": "Gagal membaca frame dari RTSP."})
            return

        # ------------------------------------------------------------------
        # Tambah overlay info di bagian bawah frame
        # ------------------------------------------------------------------
        import datetime as _dt
        ts     = _dt.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        label  = "{} | {} | {}".format(rfid_uid, status.upper(), ts)
        h, w   = frame.shape[:2]

        overlay = frame.copy()
        cv2.rectangle(overlay, (0, h - 40), (w, h), (0, 0, 0), -1)
        frame = cv2.addWeighted(overlay, 0.55, frame, 0.45, 0)

        color = (0, 210, 80) if status == "granted" else (30, 60, 230)
        cv2.putText(
            frame, label,
            (12, h - 12),
            cv2.FONT_HERSHEY_SIMPLEX, 0.58, color, 1, cv2.LINE_AA,
        )

        # ------------------------------------------------------------------
        # Encode ke JPEG -> base64
        # ------------------------------------------------------------------
        ok, buf = cv2.imencode(".jpg", frame, [cv2.IMWRITE_JPEG_QUALITY, quality])
        if not ok or buf is None:
            _out({"ok": False, "snapshot_b64": "", "error": "Gagal encode frame ke JPEG."})
            return

        b64 = base64.b64encode(buf.tobytes()).decode("ascii")
        _out({"ok": True, "snapshot_b64": b64})

    except Exception as exc:
        _out({"ok": False, "snapshot_b64": "", "error": str(exc)})
    finally:
        if cap is not None:
            try:
                cap.release()
            except Exception:
                pass


if __name__ == "__main__":
    main()