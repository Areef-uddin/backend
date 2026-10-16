<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('email', 'rajesh.kumar@zivbiyt.com')->first();
$token = $u->createToken('t')->plainTextToken;

$ch = curl_init('http://127.0.0.1:8000/api/v1/attendance/reset-today');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Reset HTTP code: $code\n";
echo "Reset Response: $res\n";

$recs = App\Models\AttendanceRecord::where('employee_id', 1)->get();
echo "Remaining records count: " . $recs->count() . "\n";
