<?php
// Test SEQUENTIAL: face_verify.py dulu, lalu cctv_capture.py
// Simulasi persis seperti web request verifyFace()
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Storage;

$pythonBin   = env('PYTHON_PATH', 'python');
$faceScript  = env('FACE_VERIFY_SCRIPT', base_path('..') . DIRECTORY_SEPARATOR . 'sakti-python' . DIRECTORY_SEPARATOR . 'face_verify.py');
$cctvScript  = env('CCTV_CAPTURE_SCRIPT', base_path('..') . DIRECTORY_SEPARATOR . 'sakti-python' . DIRECTORY_SEPARATOR . 'cctv_capture.py');

echo "Python:     {$pythonBin}" . PHP_EOL;
echo "FaceScript: {$faceScript} [" . (file_exists($faceScript) ? 'OK' : 'MISSING') . "]" . PHP_EOL;
echo "CctvScript: {$cctvScript} [" . (file_exists($cctvScript) ? 'OK' : 'MISSING') . "]" . PHP_EOL;
echo PHP_EOL;

// ---- Step 1: face_verify.py (same as web) ----
echo "[1] Running face_verify.py ..." . PHP_EOL;
$t1 = microtime(true);

$faceProc = new Process([$pythonBin, $faceScript]);
$faceProc->setInput(json_encode([
    'rfid_uid'       => 'TEST001',
    'face_image_b64' => 'dGVzdA==',  // minimal valid base64
    'face_image_ref' => 'dGVzdA==',
]));
$faceProc->setTimeout(60);
$faceProc->setEnv(['PYTHONIOENCODING' => 'utf-8', 'PYTHONUNBUFFERED' => '1']);
$faceProc->run();

$t2 = microtime(true);
$faceOut = $faceProc->getOutput();
echo "  Exit: " . $faceProc->getExitCode() . " | Time: " . round($t2 - $t1, 2) . "s" . PHP_EOL;
echo "  Stdout len: " . strlen($faceOut) . PHP_EOL;

$jstart = strpos($faceOut, '{');
$jend   = strrpos($faceOut, '}');
if ($jstart !== false && $jend !== false) {
    $faceResult = json_decode(substr($faceOut, $jstart, $jend - $jstart + 1), true);
    echo "  Face result keys: " . implode(', ', array_keys($faceResult ?? [])) . PHP_EOL;
}
echo PHP_EOL;

// ---- Step 2: cctv_capture.py (right after face_verify, same as web) ----
echo "[2] Running cctv_capture.py ..." . PHP_EOL;
$t3 = microtime(true);

$cctvProc = new Process([$pythonBin, $cctvScript]);
$cctvProc->setInput(json_encode(['rfid_uid' => 'TEST001', 'status' => 'granted']));
$cctvProc->setTimeout(20);
$cctvProc->setEnv(['PYTHONIOENCODING' => 'utf-8', 'PYTHONUNBUFFERED' => '1']);
$cctvProc->run();

$t4 = microtime(true);
$cctvOut    = $cctvProc->getOutput();
$cctvStderr = $cctvProc->getErrorOutput();

echo "  Exit: " . $cctvProc->getExitCode() . " | Time: " . round($t4 - $t3, 2) . "s" . PHP_EOL;
echo "  Stdout len: " . strlen($cctvOut) . PHP_EOL;
echo "  Stderr: " . substr($cctvStderr, 0, 200) . PHP_EOL;

$cstart = strpos($cctvOut, '{');
$cend   = strrpos($cctvOut, '}');
if ($cstart !== false && $cend !== false) {
    $cctvResult  = json_decode(substr($cctvOut, $cstart, $cend - $cstart + 1), true);
    $snapshotB64 = $cctvResult['snapshot_b64'] ?? '';
    echo "  JSON ok: " . ($cctvResult['ok'] ? 'true' : 'false') . PHP_EOL;
    echo "  snapshot_b64 len: " . strlen($snapshotB64) . PHP_EOL;
    echo "  error: " . ($cctvResult['error'] ?? '') . PHP_EOL;

    // Step 3: Save to storage
    if (!empty($snapshotB64)) {
        $imgData = base64_decode($snapshotB64, true);
        if ($imgData !== false && strlen($imgData) > 500) {
            $filename = 'snapshots/test_sequential_' . date('Ymd_His') . '.jpg';
            Storage::disk('public')->put($filename, $imgData);
            $savedPath = storage_path('app/public/' . $filename);
            echo PHP_EOL;
            echo "[3] Saved to: {$savedPath}" . PHP_EOL;
            echo "    File exists: " . (file_exists($savedPath) ? 'YES (' . filesize($savedPath) . ' bytes)' : 'NO') . PHP_EOL;
        }
    }
} else {
    echo "  NO JSON in output!" . PHP_EOL;
    echo "  Raw output: " . substr($cctvOut, 0, 200) . PHP_EOL;
}

echo PHP_EOL . "Total time: " . round($t4 - $t1, 2) . "s" . PHP_EOL;

