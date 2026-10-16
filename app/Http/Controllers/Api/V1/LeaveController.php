<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    /**
     * Get all leave types
     */
    public function types()
    {
        $types = LeaveType::all();
        return response()->json([
            'status' => 'success',
            'data' => $types,
        ]);
    }

    /**
     * Get current employee's leave balances
     */
    public function balances(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $year = Carbon::now()->year;
        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->get();

        if ($balances->isEmpty()) {
            $types = LeaveType::all();
            foreach ($types as $lt) {
                LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $lt->id,
                        'year' => $year,
                    ],
                    [
                        'opening_balance' => $lt->days_per_year,
                        'accrued' => $lt->days_per_year,
                        'used' => 0,
                        'balance' => $lt->days_per_year,
                    ]
                );
            }
            $balances = LeaveBalance::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', $year)
                ->get();
        }

        return response()->json([
            'status' => 'success',
            'year' => $year,
            'data' => $balances,
        ]);
    }

    /**
     * Get employee's own leave requests
     */
    public function myRequests(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $requests = LeaveRequest::with('leaveType')
            ->where('employee_id', $employee->id)
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    /**
     * Apply for leave
     */
    public function apply(Request $request)
    {
        $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
        ]);

        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $start = Carbon::parse($request->start_date);
        $end = Carbon::parse($request->end_date);
        $totalDays = $start->diffInDays($end) + 1;

        $leaveRequest = LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $request->leave_type_id,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'total_days' => $totalDays,
            'reason' => $request->reason,
            'status' => 'pending',
        ]);

        AuditLog::log('leave.applied', 'LeaveRequest', $leaveRequest->id, "Employee {$employee->employee_code} applied for {$totalDays} days leave.");

        return response()->json([
            'status' => 'success',
            'message' => 'Leave application submitted successfully.',
            'data' => $leaveRequest->load('leaveType'),
        ], 201);
    }

    /**
     * Admin view of all leave requests
     */
    public function adminList(Request $request)
    {
        $query = LeaveRequest::with(['employee.user', 'employee.department', 'leaveType', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $requests = $query->latest()->paginate(25);

        return response()->json([
            'status' => 'success',
            'data' => $requests,
        ]);
    }

    /**
     * Approve leave
     */
    public function approve(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::with('employee')->findOrFail($id);

        $leaveRequest->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approval_remarks' => $request->remarks ?? 'Approved by HR/Manager',
            'approved_at' => now(),
        ]);

        // Deduct from leave balance
        $year = Carbon::parse($leaveRequest->start_date)->year;
        $balance = LeaveBalance::where('employee_id', $leaveRequest->employee_id)
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $year)
            ->first();

        if ($balance) {
            $balance->used += $leaveRequest->total_days;
            $balance->balance = max(0, $balance->balance - $leaveRequest->total_days);
            $balance->save();
        }

        AuditLog::log('leave.approved', 'LeaveRequest', $leaveRequest->id, "Leave request approved for employee {$leaveRequest->employee->employee_code}.");

        return response()->json([
            'status' => 'success',
            'message' => 'Leave request approved.',
            'data' => $leaveRequest,
        ]);
    }

    /**
     * Reject leave
     */
    public function reject(Request $request, $id)
    {
        $leaveRequest = LeaveRequest::with('employee')->findOrFail($id);

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by' => $request->user()->id,
            'approval_remarks' => $request->remarks ?? 'Rejected by HR/Manager',
            'approved_at' => now(),
        ]);

        AuditLog::log('leave.rejected', 'LeaveRequest', $leaveRequest->id, "Leave request rejected for employee {$leaveRequest->employee->employee_code}.");

        return response()->json([
            'status' => 'success',
            'message' => 'Leave request rejected.',
            'data' => $leaveRequest,
        ]);
    }
}
