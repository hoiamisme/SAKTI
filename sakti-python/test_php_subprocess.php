<?php
// Test: simulasi PHP memanggil face_verify.py persis seperti ScanController
require __DIR__ . '/../sakti-backend/vendor/autoload.php';

use Symfony\Component\Process\Process;

$pythonBin  = 'C:\\SAKTI\\.venv\\Scripts\\python.exe';
$scriptPath = 'C:\\SAKTI\\sakti-python\\face_verify.py';

// Buat dummy image 80x80 (warna solid - bukan wajah nyata)
$imgW = 80; $imgH = 80;
$img = imagecreatetruecolor($imgW, $imgH);
$skin = imagecolorallocate($img, 180, 140, 110);
imagefill($img, 0, 0, $skin);
ob_start();
imagejpeg($img, null, 90);
$imgData = ob_get_clean();
imagedestroy($img);
$dummyB64 = base64_encode($imgData);

$payload = json_encode([
    'rfid_uid'       => 'TEST001',
    'face_image_b64' => $dummyB64,
    'face_image_ref' => $dummyB64,
]);

$process = new Process([$pythonBin, $scriptPath]);
$process->setInput($payload);
$process->setTimeout(30);
$process->setEnv(['PYTHONIOENCODING' => 'utf-8', 'PYTHONUNBUFFERED' => '1']);
$process->run();

echo "Exit code: " . $process->getExitCode() . PHP_EOL;
echo "Stdout length: " . strlen($process->getOutput()) . PHP_EOL;
echo "Stderr tail: " . substr($process->getErrorOutput(), -300) . PHP_EOL;

$rawOutput = $process->getOutput();
$jsonStart = strpos($rawOutput, '{');
$jsonEnd   = strrpos($rawOutput, '}');
if ($jsonStart !== false && $jsonEnd !== false) {
    $jsonStr = substr($rawOutput, $jsonStart, $jsonEnd - $jsonStart + 1);
    $result  = json_decode($jsonStr, true);
    echo "JSON decode: " . (is_array($result) ? "OK" : "FAILED") . PHP_EOL;
    if (is_array($result)) {
        echo "  match: " . var_export($result['match'] ?? null, true) . PHP_EOL;
        echo "  keterangan: " . ($result['keterangan'] ?? '') . PHP_EOL;
        echo "  snapshot_b64 length: " . strlen($result['snapshot_b64'] ?? '') . PHP_EOL;
    }
} else {
    echo "No JSON found in output!" . PHP_EOL;
    echo "First 500 chars: " . substr($rawOutput, 0, 500) . PHP_EOL;
}
