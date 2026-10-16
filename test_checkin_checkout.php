<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('email', 'rajesh.kumar@zivbiyt.com')->first();
$token = $u->createToken('test')->plainTextToken;

function callApi($url, $method = 'GET', $data = null, $token = '') {
    $ch = curl_init($url);
    $headers = ['Accept: application/json', 'Content-Type: application/json'];
    if ($token) $headers[] = 'Authorization: Bearer ' . $token;
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

// 1. Reset today
echo "1. Resetting today...\n";
$r = callApi('http://127.0.0.1:8000/api/v1/attendance/reset-today', 'POST', [], $token);
echo "Reset: " . $r['code'] . " - " . $r['body'] . "\n";

// 2. Check In
echo "\n2. Testing Check In...\n";
$r = callApi('http://127.0.0.1:8000/api/v1/attendance/check-in', 'POST', [
    'latitude' => 12.9342,
    'longitude' => 77.6101,
    'address' => 'Koramangala 4th Block, Bangalore'
], $token);
echo "Check In 1: " . $r['code'] . " - " . $r['body'] . "\n";

// 3. Check In again (Idempotent test - should succeed gracefully without throwing!)
echo "\n3. Testing Second Check In (Idempotent)...\n";
$r = callApi('http://127.0.0.1:8000/api/v1/attendance/check-in', 'POST', [
    'latitude' => 12.9350,
    'longitude' => 77.6110,
    'address' => 'Updated Koramangala 4th Block, Bangalore'
], $token);
echo "Check In 2: " . $r['code'] . " - " . $r['body'] . "\n";

// 4. Status Check
echo "\n4. Checking Status...\n";
$r = callApi('http://127.0.0.1:8000/api/v1/attendance/today', 'GET', null, $token);
echo "Today Status: " . $r['code'] . " - " . $r['body'] . "\n";

// 5. Check Out
echo "\n5. Testing Check Out...\n";
$r = callApi('http://127.0.0.1:8000/api/v1/attendance/check-out', 'POST', [
    'latitude' => 12.9345,
    'longitude' => 77.6105,
    'address' => 'Check-out Bangalore'
], $token);
echo "Check Out 1: " . $r['code'] . " - " . $r['body'] . "\n";

// 6. Check Out again (Idempotent test)
echo "\n6. Testing Second Check Out (Idempotent)...\n";
$r = callApi('http://127.0.0.1:8000/api/v1/attendance/check-out', 'POST', [
    'latitude' => 12.9345,
    'longitude' => 77.6105,
    'address' => 'Check-out Bangalore'
], $token);
echo "Check Out 2: " . $r['code'] . " - " . $r['body'] . "\n";

// 7. Reset back to clean state
echo "\n7. Resetting to fresh state for user...\n";
$r = callApi('http://127.0.0.1:8000/api/v1/attendance/reset-today', 'POST', [], $token);
echo "Reset: " . $r['code'] . " - " . $r['body'] . "\n";
