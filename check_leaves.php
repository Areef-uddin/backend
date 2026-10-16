<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::where('email', 'rajesh.kumar@zivbiyt.com')->first();
echo "User: " . $user->name . " (ID: {$user->id})\n";
if ($user->employee) {
    echo "Employee ID: " . $user->employee->id . "\n";
    $balances = App\Models\LeaveBalance::with('leaveType')->where('employee_id', $user->employee->id)->get();
    echo "Balances count: " . $balances->count() . "\n";
    foreach ($balances as $b) {
        echo " - " . ($b->leaveType ? $b->leaveType->name : 'Unknown') . ": balance=" . $b->balance . ", used=" . $b->used . "\n";
    }
    $requests = App\Models\LeaveRequest::with('leaveType')->where('employee_id', $user->employee->id)->latest()->get();
    echo "Requests count: " . $requests->count() . "\n";
    foreach ($requests as $r) {
        echo " - Request ID {$r->id}: {$r->total_days} days (" . ($r->leaveType ? $r->leaveType->name : 'N/A') . ") status={$r->status} reason='{$r->reason}'\n";
    }
}
