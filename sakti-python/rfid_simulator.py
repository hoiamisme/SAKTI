# =============================================================================
# File   : rfid_simulator.py
# Fungsi : Membaca RFID UID dari serial port (mode real) atau dari input
#          terminal (mode simulasi). Dikontrol via RFID_SIMULATION_MODE di .env.
# Author : SAKTI Dev Team
# Date   : 2026-05-01
# =============================================================================

import time
from typing import Optional

import config
from utils.logger import get_logger

logger = get_logger(__name__)


# =============================================================================
# Mode Real — Serial Port (pyserial)
# =============================================================================

def _read_rfid_serial(timeout: float = 30.0) -> Optional[str]:
    """
    Baca RFID UID dari serial port.
    Menunggu data masuk hingga `timeout` detik.

    Returns:
        UID string (strip whitespace) atau None jika timeout/error.
    """
    try:
        import serial  # type: ignore
    except ImportError:
        logger.error("Library 'pyserial' tidak terinstall. Jalankan: pip install pyserial")
        return None

    try:
        logger.info(
            "Membuka serial port %s @ %d baud...",
            config.RFID_PORT,
            config.RFID_BAUDRATE,
        )
        with serial.Serial(
            port=config.RFID_PORT,
            baudrate=config.RFID_BAUDRATE,
            timeout=1,
        ) as ser:
            logger.info("Tap kartu RFID sekarang... (timeout: %.0fs)", timeout)
            start = time.monotonic()

            while time.monotonic() - start < timeout:
                if ser.in_waiting > 0:
                    raw = ser.readline()
                    uid = raw.decode("utf-8", errors="ignore").strip()
                    if uid:
                        logger.info("RFID UID diterima dari serial: %s", uid)
                        return uid
                time.sleep(0.05)

            logger.warning("Timeout menunggu RFID dari serial port.")
            return None

    except Exception as exc:
        logger.error("Gagal membaca dari serial port: %s", exc)
        return None


# =============================================================================
# Mode Simulasi — Input Terminal
# =============================================================================

def _read_rfid_simulation() -> Optional[str]:
    """
    Baca RFID UID dari input keyboard (untuk testing tanpa hardware).

    Returns:
        UID string atau None jika user menekan Enter kosong.
    """
    print("\n" + "=" * 50)
    print("  [SIMULASI] Masukkan RFID UID secara manual")
    print("  (Kosongkan + Enter untuk kembali ke menu)")
    print("=" * 50)

    try:
        uid = input("  RFID UID >> ").strip()
    except (KeyboardInterrupt, EOFError):
        return None

    if not uid:
        return None

    logger.info("RFID UID dari simulasi: %s", uid)
    return uid


# =============================================================================
# Public API
# =============================================================================

def read_rfid(timeout: float = 30.0) -> Optional[str]:
    """
    Baca RFID UID sesuai mode yang dikonfigurasi di .env.

    Args:
        timeout: Detik tunggu maksimal (hanya berlaku pada mode real).

    Returns:
        UID string atau None.
    """
    if config.RFID_SIMULATION_MODE:
        return _read_rfid_simulation()
    return _read_rfid_serial(timeout=timeout)
