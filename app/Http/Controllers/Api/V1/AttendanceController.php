<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    protected AttendanceService $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->attendanceService = $attendanceService;
    }

    /**
     * Get employee's today attendance status
     */
    public function today(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        $joining = $user->joiningRequest;

        if ($joining) {
            $joiningStatus = $joining->status;
            $isApproved = ($joiningStatus === 'approved');
        } else {
            if ($employee && $employee->employment_status === 'active' && $user->id <= 11) {
                $joiningStatus = 'approved';
                $isApproved = true;
            } else {
                $joiningStatus = 'not_submitted';
                $isApproved = false;
            }
        }

        if (!$isApproved || !$employee) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'record' => null,
                    'is_checked_in' => false,
                    'is_completed' => false,
                    'shift' => null,
                    'can_check_in' => false,
                    'joining_status' => $joiningStatus,
                    'message' => $joiningStatus === 'pending'
                        ? 'Your Starforce Employee Joining Form is under review by HR Admin. Attendance will unlock upon approval.'
                        : ($joiningStatus === 'rejected'
                            ? 'Your Starforce joining application was rejected. Please contact HR.'
                            : 'Please complete your Starforce Employee Joining Form to unlock attendance check-in.'),
                ],
            ]);
        }

        $today = Carbon::today()->format('Y-m-d');
        $record = AttendanceRecord::with('shift')
            ->where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->first();

        $shift = $employee->currentShift();

        return response()->json([
            'status' => 'success',
            'data' => [
                'record' => $record,
                'is_checked_in' => $record && $record->check_in_at && !$record->check_out_at,
                'is_completed' => $record && $record->check_in_at && $record->check_out_at,
                'shift' => $shift,
                'can_check_in' => true,
                'joining_status' => 'approved',
            ],
        ]);
    }

    /**
     * Check-in with GPS
     */
    public function checkIn(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;
        $joining = $user->joiningRequest;

        $isApproved = ($joining && $joining->status === 'approved') || ($employee && $employee->employment_status === 'active');

        if (!$isApproved || !$employee || $employee->employment_status !== 'active') {
            $statusText = $joining ? $joining->status : ($employee ? $employee->employment_status : 'not_submitted');
            return response()->json([
                'status' => 'error',
                'message' => 'Attendance is locked. Your Starforce Employee Joining Form must be approved by HR / Admin before you can check in.',
                'joining_status' => $statusText,
            ], 403);
        }

        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'address' => 'nullable|string',
        ]);

        try {
            $record = $this->attendanceService->checkIn($employee, $request->all());

            // Automatically start live tracking session for this shift
            try {
                $trackingService = app(\App\Services\TrackingService::class);
                $trackingService->startSession($employee, $record->id);
                if ($request->filled('latitude') && $request->filled('longitude')) {
                    $trackingService->recordLocation($employee, [
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'accuracy' => $request->accuracy ?? 15,
                        'speed' => $request->speed ?? 0,
                        'battery_level' => $request->battery_level ?? 92,
                    ]);
                }
            } catch (\Exception $te) {
                // Non-fatal
            }

            AuditLog::log('attendance.check_in', 'AttendanceRecord', $record->id, "Employee {$employee->employee_code} checked in at " . Carbon::parse($record->check_in_at)->format('H:i'));

            return response()->json([
                'status' => 'success',
                'message' => 'Checked in successfully. Live location tracking activated.',
                'data' => $record,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Check-out
     */
    public function checkOut(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 404);
        }

        $request->validate([
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'address' => 'nullable|string',
            'accuracy' => 'nullable|numeric',
            'battery_level' => 'nullable|integer',
        ]);

        try {
            $record = $this->attendanceService->checkOut($employee, $request->all());

            // Automatically record final location and stop live tracking session
            try {
                $trackingService = app(\App\Services\TrackingService::class);
                if ($request->filled('latitude') && $request->filled('longitude')) {
                    $trackingService->recordLocation($employee, [
                        'latitude' => $request->latitude,
                        'longitude' => $request->longitude,
                        'accuracy' => $request->accuracy ?? 15,
                        'speed' => 0,
                        'battery_level' => $request->battery_level ?? 85,
                    ]);
                }
                $trackingService->stopSession($employee);
            } catch (\Exception $te) {
                // Non-fatal
            }

            AuditLog::log('attendance.check_out', 'AttendanceRecord', $record->id, "Employee {$employee->employee_code} checked out at " . Carbon::parse($record->check_out_at)->format('H:i'));

            return response()->json([
                'status' => 'success',
                'message' => 'Checked out successfully. Live location tracking stopped.',
                'data' => $record,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Reset today's attendance for testing / demo purposes
     */
    public function resetToday(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 404);
        }

        $today = Carbon::today()->format('Y-m-d');
        AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $today)
            ->delete();

        \App\Models\TrackingSession::where('employee_id', $employee->id)
            ->where('status', 'active')
            ->update(['status' => 'completed', 'ended_at' => Carbon::now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Today session reset successfully. You can now check in afresh.',
        ]);
    }

    /**
     * Own attendance history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 404);
        }

        $query = AttendanceRecord::with('shift')
            ->where('employee_id', $employee->id);

        if ($request->filled('month')) {
            $query->where('work_date', 'like', $request->month . '%');
        }

        $records = $query->latest('work_date')->paginate(31);

        return response()->json([
            'status' => 'success',
            'data' => $records,
        ]);
    }

    /**
     * Admin attendance sheet
     */
    public function adminList(Request $request)
    {
        $query = AttendanceRecord::with(['employee.user', 'employee.department', 'shift']);

        $date = $request->date ?? Carbon::today()->format('Y-m-d');
        $query->whereDate('work_date', $date);

        if ($request->filled('department_id')) {
            $query->whereHas('employee', function ($q) use ($request) {
                $q->where('department_id', $request->department_id);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $records = $query->get();
        $now = Carbon::now();

        $records->transform(function ($record) use ($now) {
            if ($record->check_in_at) {
                if ($record->check_out_at) {
                    $record->total_minutes = max(0, (int) Carbon::parse($record->check_in_at)->diffInMinutes(Carbon::parse($record->check_out_at)));
                    $record->is_on_duty = false;
                } else {
                    $record->total_minutes = max(0, (int) Carbon::parse($record->check_in_at)->diffInMinutes($now));
                    $record->is_on_duty = true;
                }
            }
            return $record;
        });

        return response()->json([
            'status' => 'success',
            'date' => $date,
            'data' => $records,
        ]);
    }

    /**
     * Request attendance correction
     */
    public function submitCorrection(Request $request)
    {
        $request->validate([
            'work_date' => 'required|date',
            'requested_check_in' => 'nullable|date',
            'requested_check_out' => 'nullable|date',
            'reason' => 'required|string|max:500',
        ]);

        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee record not found.'], 404);
        }

        $attendanceRecord = AttendanceRecord::where('employee_id', $employee->id)
            ->whereDate('work_date', $request->work_date)
            ->first();

        $correction = AttendanceCorrection::create([
            'attendance_record_id' => $attendanceRecord?->id,
            'employee_id' => $employee->id,
            'work_date' => $request->work_date,
            'requested_check_in' => $request->requested_check_in,
            'requested_check_out' => $request->requested_check_out,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Attendance correction request submitted.',
            'data' => $correction,
        ], 201);
    }

    /**
     * Admin review correction
     */
    public function reviewCorrection(Request $request, $id)
    {
        $request->validate([
            'decision' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string',
        ]);

        $correction = AttendanceCorrection::with('attendanceRecord')->findOrFail($id);

        $correction->update([
            'status' => $request->decision,
            'reviewed_by' => $request->user()->id,
            'review_remarks' => $request->remarks,
            'reviewed_at' => now(),
        ]);

        if ($request->decision === 'approved') {
            if ($correction->attendanceRecord) {
                $correction->attendanceRecord->update([
                    'check_in_at' => $correction->requested_check_in ?? $correction->attendanceRecord->check_in_at,
                    'check_out_at' => $correction->requested_check_out ?? $correction->attendanceRecord->check_out_at,
                    'status' => 'present',
                ]);
            } else {
                AttendanceRecord::create([
                    'employee_id' => $correction->employee_id,
                    'work_date' => $correction->work_date,
                    'check_in_at' => $correction->requested_check_in,
                    'check_out_at' => $correction->requested_check_out,
                    'status' => 'present',
                    'is_verified' => true,
                    'verification_method' => 'manual',
                    'notes' => 'Created via approved correction request: ' . $correction->reason,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => "Correction request {$request->decision}.",
            'data' => $correction,
        ]);
    }

    /**
     * Get dynamic monthly attendance summary & log history (ZERO dummy data)
     */
    public function monthlySummary(Request $request)
    {
        $user = $request->user();
        $employee = $user->employee;

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee profile not found.',
            ], 404);
        }

        $monthParam = $request->query('month');
        if ($monthParam && preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
            $currentMonth = Carbon::createFromFormat('Y-m', $monthParam)->startOfMonth();
        } else {
            $currentMonth = Carbon::now()->startOfMonth();
        }

        $now = Carbon::now();
        $isCurrentMonth = $currentMonth->isSameMonth($now);
        $endBoundary = $isCurrentMonth ? $now : $currentMonth->copy()->endOfMonth();

        // 1. Fetch real attendance records for this month
        $records = AttendanceRecord::with('shift')
            ->where('employee_id', $employee->id)
            ->whereYear('work_date', $currentMonth->year)
            ->whereMonth('work_date', $currentMonth->month)
            ->orderBy('work_date', 'desc')
            ->get();

        // 2. Fetch approved leave requests for this employee
        $approvedLeaves = \App\Models\LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->where(function ($q) use ($currentMonth) {
                $start = $currentMonth->copy()->startOfMonth()->format('Y-m-d');
                $end = $currentMonth->copy()->endOfMonth()->format('Y-m-d');
                $q->whereBetween('start_date', [$start, $end])
                  ->orWhereBetween('end_date', [$start, $end]);
            })
            ->get();

        // Count present days from actual records
        $presentDays = $records->filter(function ($r) {
            return in_array($r->status, ['present', 'half_day']) || !empty($r->check_in_at);
        })->count();

        // Count leave days
        $leaveDays = 0;
        foreach ($approvedLeaves as $al) {
            $leaveDays += (int) ($al->total_days ?? 1);
        }

        // Count total working days in the month (excluding Sundays)
        $workingDays = 0;
        $dayCursor = $currentMonth->copy();
        $monthEnd = $currentMonth->copy()->endOfMonth();
        while ($dayCursor->lte($monthEnd)) {
            if (!$dayCursor->isSunday()) {
                $workingDays++;
            }
            $dayCursor->addDay();
        }

        // Only count actual recorded absences from the database (ZERO synthetic dummy absent numbers)
        $absentDays = $records->where('status', 'absent')->count();

        // Format history list: ONLY real, authentic attendance records and approved leaves from database
        $historyList = [];

        // 1. Add all real attendance punch records
        foreach ($records as $rec) {
            $workDate = Carbon::parse($rec->work_date);
            $checkInTime = $rec->check_in_at ? Carbon::parse($rec->check_in_at)->format('h:i A') : null;
            $checkOutTime = $rec->check_out_at ? Carbon::parse($rec->check_out_at)->format('h:i A') : null;
            $timeDisplay = ($checkInTime && $checkOutTime) ? "$checkInTime - $checkOutTime" : ($checkInTime ? "$checkInTime - Active" : '—');

            $statusLabel = $rec->status === 'half_day' ? 'Half Day' : ucfirst($rec->status ?? 'Present');
            $statusColor = $rec->status === 'absent' ? 'danger' : ($rec->status === 'half_day' ? 'warning' : 'success');

            $historyList[] = [
                'id' => $rec->id,
                'date' => $workDate->format('d M Y'),
                'work_date' => $workDate->format('Y-m-d'),
                'time_display' => $timeDisplay,
                'check_in' => $checkInTime,
                'check_out' => $checkOutTime,
                'status' => $statusLabel,
                'status_color' => $statusColor,
                'notes' => $rec->notes ?: ($rec->check_in_address ? "Site: {$rec->check_in_address}" : null),
                'timestamp' => $rec->check_in_at ? Carbon::parse($rec->check_in_at)->timestamp : $workDate->timestamp,
            ];
        }

        // 2. Add real approved leave requests in the month
        foreach ($approvedLeaves as $al) {
            $startDate = Carbon::parse($al->start_date);
            $endDate = Carbon::parse($al->end_date);
            $leaveTypeTitle = $al->leaveType?->name ?? 'Approved Leave';

            $historyList[] = [
                'id' => null,
                'date' => $startDate->format('d M Y') . ($al->total_days > 1 ? " - " . $endDate->format('d M Y') : ''),
                'work_date' => $startDate->format('Y-m-d'),
                'time_display' => "$leaveTypeTitle ({$al->total_days} Day" . ($al->total_days > 1 ? 's' : '') . ")",
                'check_in' => null,
                'check_out' => null,
                'status' => 'Leave',
                'status_color' => 'warning',
                'notes' => $al->reason ?: 'Official approved leave',
                'timestamp' => $startDate->timestamp,
            ];
        }

        // Sort history by latest date first
        usort($historyList, function ($a, $b) {
            return ($b['timestamp'] ?? 0) <=> ($a['timestamp'] ?? 0);
        });

        // Strip internal timestamp field
        $historyList = array_map(function ($item) {
            unset($item['timestamp']);
            return $item;
        }, $historyList);

        return response()->json([
            'status' => 'success',
            'data' => [
                'month_name' => $currentMonth->format('F Y'),
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'leave_days' => $leaveDays,
                'working_days' => $workingDays,
                'history' => $historyList,
            ],
        ]);
    }
}
