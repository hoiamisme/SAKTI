"""
Test end-to-end face_verify.py persis seperti PHP memanggilnya.
Membuat gambar dummy (wajah palsu) lalu pipe ke stdin face_verify.py.
"""
import subprocess, json, base64, sys, os
import numpy as np
import cv2

# Buat gambar muka palsu 160x160 (kulit + lingkaran mata)
img = np.zeros((160, 160, 3), dtype=np.uint8)
img[:] = (180, 140, 110)   # warna kulit
cv2.circle(img, (60, 70), 12, (50, 30, 20), -1)   # mata kiri
cv2.circle(img, (100, 70), 12, (50, 30, 20), -1)  # mata kanan
cv2.ellipse(img, (80, 110), (30, 15), 0, 0, 180, (80, 40, 30), 2)  # mulut
_, buf = cv2.imencode('.jpg', img, [cv2.IMWRITE_JPEG_QUALITY, 90])
dummy_b64 = base64.b64encode(buf.tobytes()).decode('ascii')

payload = json.dumps({
    "rfid_uid":       "TEST001",
    "face_image_b64": dummy_b64,
    "face_image_ref": dummy_b64,   # sama = pasti match
})

python_bin  = r"C:\SAKTI\.venv\Scripts\python.exe"
script_path = r"C:\SAKTI\sakti-python\face_verify.py"

result = subprocess.run(
    [python_bin, script_path],
    input=payload,
    capture_output=True,
    text=True,
    encoding="utf-8",
    timeout=30,
)

print("=== STDOUT ===")
print(repr(result.stdout[:500]))
print("=== STDERR (last 800) ===")
print(result.stderr[-800:])
print("=== EXIT ===", result.returncode)

# Parse JSON
raw = result.stdout
j_start = raw.find('{')
j_end   = raw.rfind('}')
if j_start != -1 and j_end != -1:
    parsed = json.loads(raw[j_start:j_end+1])
    print("\n=== PARSED ===")
    for k, v in parsed.items():
        if k == 'snapshot_b64':
            print(f"  snapshot_b64: {'[EMPTY]' if not v else f'[{len(v)} chars]'}")
        else:
            print(f"  {k}: {v}")
else:
    print("No valid JSON in stdout!")
