<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\PayrollRun;
use App\Models\TrackingSession;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Admin Dashboard Overview & KPIs
     */
    public function kpi()
    {
        $today = Carbon::today()->format('Y-m-d');
        $currentMonth = Carbon::today()->format('Y-m');

        $totalEmployees = Employee::where('employment_status', 'active')->count();

        $presentToday = AttendanceRecord::whereDate('work_date', $today)
            ->whereIn('status', ['present', 'late'])
            ->count();

        $onDutyFieldStaff = TrackingSession::where('status', 'active')->count();

        $pendingLeaves = LeaveRequest::where('status', 'pending')->count();

        $latestPayroll = PayrollRun::latest('payroll_month')->first();

        $departmentsCount = Department::withCount('employees')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_employees' => $totalEmployees,
                'present_today' => $presentToday,
                'attendance_percentage' => $totalEmployees > 0 ? round(($presentToday / $totalEmployees) * 100, 1) : 0,
                'on_duty_field_staff' => $onDutyFieldStaff,
                'pending_leaves' => $pendingLeaves,
                'current_month' => $currentMonth,
                'latest_payroll' => $latestPayroll ? [
                    'id' => $latestPayroll->id,
                    'month' => $latestPayroll->payroll_month,
                    'status' => $latestPayroll->status,
                    'total_gross' => $latestPayroll->total_gross,
                    'total_deductions' => $latestPayroll->total_deductions,
                    'total_net' => $latestPayroll->total_net_pay,
                    'total_employees' => $latestPayroll->total_employees,
                ] : null,
                'departments' => $departmentsCount->map(function ($dept) {
                    return [
                        'id' => $dept->id,
                        'name' => $dept->name,
                        'count' => $dept->employees_count,
                    ];
                }),
            ],
        ]);
    }
}
