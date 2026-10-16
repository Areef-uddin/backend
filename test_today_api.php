<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$u = App\Models\User::where('email', 'rajesh.kumar@zivbiyt.com')->first();
$t = $u->createToken('t')->plainTextToken;

$ch = curl_init('http://127.0.0.1:8000/api/v1/attendance/today');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Bearer ' . $t]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
curl_close($ch);

echo "Today API Response:\n" . $res . "\n";
