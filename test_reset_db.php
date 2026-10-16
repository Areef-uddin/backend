<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$today = Carbon\Carbon::today()->format('Y-m-d');
echo "Carbon today: $today\n";
$records = App\Models\AttendanceRecord::where('employee_id', 1)->get();
foreach ($records as $r) {
    echo "ID: {$r->id}, work_date raw: '{$r->getRawOriginal('work_date')}', cast: '{$r->work_date}', check_in: '{$r->check_in_at}', check_out: '{$r->check_out_at}'\n";
}

$c1 = App\Models\AttendanceRecord::where('employee_id', 1)->whereDate('work_date', $today)->count();
echo "Count whereDate: $c1\n";

$c2 = App\Models\AttendanceRecord::where('employee_id', 1)->where('work_date', 'like', "$today%")->count();
echo "Count where like: $c2\n";
