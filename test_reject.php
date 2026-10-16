<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$admin = App\Models\User::where('email', 'admin@zivbiyt.com')->first();
$token = $admin->createToken('admin_test')->plainTextToken;

$req = App\Models\LeaveRequest::latest()->first();
echo "Testing reject on LeaveRequest ID: {$req->id}, current status: {$req->status}\n";

$ch = curl_init("http://127.0.0.1:8000/api/v1/leave/requests/{$req->id}/reject");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token,
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['remarks' => 'Test rejection from admin']));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Status: $code\nResponse: $res\n";
