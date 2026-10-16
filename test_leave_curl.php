<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'rajesh.kumar@zivbiyt.com')->first();
$token = $user->createToken('test')->plainTextToken;

$ch = curl_init('http://127.0.0.1:8000/api/v1/leave/balances');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Balances HTTP code: $httpCode\n";
echo "Balances Response: $res\n\n";

$ch2 = curl_init('http://127.0.0.1:8000/api/v1/leave/requests');
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
]);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
$res2 = curl_exec($ch2);
$httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "Requests HTTP code: $httpCode2\n";
echo "Requests Response: $res2\n\n";

$ch3 = curl_init('http://127.0.0.1:8000/api/v1/leave/types');
curl_setopt($ch3, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
]);
curl_setopt($ch3, CURLOPT_RETURNTRANSFER, true);
$res3 = curl_exec($ch3);
$httpCode3 = curl_getinfo($ch3, CURLINFO_HTTP_CODE);
curl_close($ch3);

echo "Types HTTP code: $httpCode3\n";
echo "Types Response: $res3\n";
