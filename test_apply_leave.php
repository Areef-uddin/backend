<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'rajesh.kumar@zivbiyt.com')->first();
$token = $user->createToken('test_apply')->plainTextToken;

function testPost($token, $payload) {
    $ch = curl_init('http://127.0.0.1:8000/api/v1/leave/requests');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "Status: $code\nResponse: $res\n";
}

echo "--- Test 1: Today start date ---\n";
testPost($token, [
    'leave_type_id' => 1,
    'start_date' => '2026-10-15',
    'end_date' => '2026-10-16',
    'reason' => 'Emergency personal work today',
]);

echo "\n--- Test 2: Tomorrow start date ---\n";
testPost($token, [
    'leave_type_id' => 2,
    'start_date' => '2026-10-16',
    'end_date' => '2026-10-18',
    'reason' => 'Medical checkup',
]);
