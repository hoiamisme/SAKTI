# =============================================================================
# File   : utils/logger.py
# Fungsi : Konfigurasi logging terpusat — output ke console & file sekaligus.
#          Semua modul mengimpor logger dari sini.
# Author : SAKTI Dev Team
# Date   : 2026-05-01
# =============================================================================

import logging
import sys
from logging.handlers import RotatingFileHandler

import config

_FORMATTER = logging.Formatter(
    fmt="%(asctime)s | %(levelname)-8s | %(name)s | %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)


def get_logger(name: str) -> logging.Logger:
    """
    Kembalikan logger dengan nama tertentu.
    Konfigurasi (level, handler) ditentukan dari config.py (baca dari .env).
    Aman dipanggil berkali-kali — handler tidak akan duplikat.
    """
    logger = logging.getLogger(name)

    if logger.handlers:
        # Sudah dikonfigurasi sebelumnya, langsung kembalikan
        return logger

    level = getattr(logging, config.LOG_LEVEL, logging.INFO)
    logger.setLevel(level)

    # --- Handler: Console ---
    console_handler = logging.StreamHandler(sys.stdout)
    console_handler.setLevel(level)
    console_handler.setFormatter(_FORMATTER)
    logger.addHandler(console_handler)

    # --- Handler: File (rotating, max 5 MB, simpan 3 backup) ---
    try:
        file_handler = RotatingFileHandler(
            filename=config.LOG_FILE,
            maxBytes=5 * 1024 * 1024,  # 5 MB
            backupCount=3,
            encoding="utf-8",
        )
        file_handler.setLevel(level)
        file_handler.setFormatter(_FORMATTER)
        logger.addHandler(file_handler)
    except OSError as exc:
        logger.warning("Gagal membuka log file '%s': %s", config.LOG_FILE, exc)

    # Cegah log naik ke root logger (hindari duplikasi)
    logger.propagate = False

    return logger
