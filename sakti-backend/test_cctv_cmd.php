<?php
// Test cctv_capture.py via cmd.exe wrapper (simulasi ScanController baru)
$pythonBin = 'C:\\SAKTI\\.venv\\Scripts\\python.exe';
$cctvScript = 'C:\\SAKTI\\sakti-python\\cctv_capture.py';

$tmpInput  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sakti_cctv_' . uniqid() . '.json';
$tmpOutput = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sakti_cctv_' . uniqid() . '.json';

file_put_contents($tmpInput, json_encode([
    'rfid_uid' => 'TEST001',
    'status'   => 'granted',
]));

$pyBin  = str_replace('/', DIRECTORY_SEPARATOR, $pythonBin);
$script = str_replace('/', DIRECTORY_SEPARATOR, $cctvScript);

$cmdLine = sprintf(
    'cmd.exe /c ""%s" "%s" < "%s" > "%s" 2>nul"',
    $pyBin, $script, $tmpInput, $tmpOutput
);

echo "CMD: $cmdLine\n";
echo "TmpIn:  $tmpInput\n";
echo "TmpOut: $tmpOutput\n";

$start = microtime(true);
exec($cmdLine, $execDummy, $execExit);
$elapsed = round(microtime(true) - $start, 2);

$cctvRaw = file_exists($tmpOutput) ? file_get_contents($tmpOutput) : '';

echo "Exit=$execExit Time={$elapsed}s\n";
echo "OutputLen=" . strlen($cctvRaw) . "\n";
echo "Head=" . substr($cctvRaw, 0, 200) . "\n";

@unlink($tmpInput);
@unlink($tmpOutput);

// Parse result
$jsonStart = strpos($cctvRaw, '{');
$jsonEnd   = strrpos($cctvRaw, '}');
if ($jsonStart !== false && $jsonEnd !== false) {
    $cctvJson   = substr($cctvRaw, $jsonStart, $jsonEnd - $jsonStart + 1);
    $cctvResult = json_decode($cctvJson, true);
    $ok         = $cctvResult['ok'] ?? false;
    $b64len     = strlen($cctvResult['snapshot_b64'] ?? '');
    $err        = $cctvResult['error'] ?? '';
    echo "ok=$ok b64len=$b64len error=$err\n";
} else {
    echo "No JSON in output!\n";
}
