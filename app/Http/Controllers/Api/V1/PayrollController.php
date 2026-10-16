<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PayrollItem;
use App\Models\PayrollRun;
use App\Models\Payslip;
use App\Services\PayrollCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PayrollController extends Controller
{
    protected PayrollCalculationService $calculationService;

    public function __construct(PayrollCalculationService $calculationService)
    {
        $this->calculationService = $calculationService;
    }

    /**
     * List all monthly payroll runs
     */
    public function index()
    {
        $runs = PayrollRun::with(['creator', 'approver'])
            ->latest('payroll_month')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $runs,
        ]);
    }

    /**
     * Create or retrieve a monthly payroll run
     */
    public function create(Request $request)
    {
        $request->validate([
            'payroll_month' => 'required|string|regex:/^\d{4}-\d{2}$/', // e.g. "2026-10"
        ]);

        $monthStr = $request->payroll_month;
        $date = Carbon::createFromFormat('Y-m', $monthStr);

        $run = PayrollRun::firstOrCreate(
            ['payroll_month' => $monthStr],
            [
                'year' => $date->year,
                'month' => $date->month,
                'status' => 'draft',
                'created_by' => $request->user()->id,
            ]
        );

        AuditLog::log('payroll.created', 'PayrollRun', $run->id, "Payroll run initialized for {$monthStr}.");

        return response()->json([
            'status' => 'success',
            'data' => $run,
        ]);
    }

    /**
     * Trigger salary calculation engine
     */
    public function calculate(Request $request, $id)
    {
        $run = PayrollRun::findOrFail($id);

        if (in_array($run->status, ['finalized', 'published'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot recalculate a finalized or published payroll run.',
            ], 422);
        }

        $result = $this->calculationService->calculateMonthlyPayroll($run);

        return response()->json([
            'status' => 'success',
            'message' => "Calculated payroll for {$result['total_employees']} employees.",
            'data' => $result,
        ]);
    }

    /**
     * Preview payroll run breakdown and exceptions
     */
    public function preview($id)
    {
        $run = PayrollRun::with([
            'creator',
            'approver',
            'items.employee.user',
            'items.employee.department',
            'items.employee.designation',
            'items.employee.bankAccount',
            'items.components',
        ])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $run,
        ]);
    }

    /**
     * Approve payroll run
     */
    public function approve(Request $request, $id)
    {
        $run = PayrollRun::findOrFail($id);

        if ($run->status !== 'calculated' && $run->status !== 'draft') {
            return response()->json([
                'status' => 'error',
                'message' => 'Payroll must be calculated before approval.',
            ], 422);
        }

        $run->update([
            'status' => 'approved',
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'notes' => $request->notes ?? $run->notes,
        ]);

        AuditLog::log('payroll.approved', 'PayrollRun', $run->id, "Payroll for {$run->payroll_month} approved by {$request->user()->name}.");

        return response()->json([
            'status' => 'success',
            'message' => 'Payroll run approved successfully.',
            'data' => $run,
        ]);
    }

    /**
     * Finalize & lock payroll run
     */
    public function finalize(Request $request, $id)
    {
        $run = PayrollRun::findOrFail($id);

        if ($run->status !== 'approved') {
            return response()->json([
                'status' => 'error',
                'message' => 'Payroll must be approved before finalization.',
            ], 422);
        }

        $run->update([
            'status' => 'finalized',
            'finalized_at' => now(),
        ]);

        AuditLog::log('payroll.finalized', 'PayrollRun', $run->id, "Payroll for {$run->payroll_month} locked and finalized.");

        return response()->json([
            'status' => 'success',
            'message' => 'Payroll finalized and locked.',
            'data' => $run,
        ]);
    }

    /**
     * Publish payslips for employees
     */
    public function publishPayslips(Request $request, $id)
    {
        $run = PayrollRun::with('items')->findOrFail($id);

        if (!in_array($run->status, ['approved', 'finalized'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payroll must be approved or finalized before publishing payslips.',
            ], 422);
        }

        $count = 0;
        foreach ($run->items as $item) {
            $payslipNumber = 'PS-' . str_replace('-', '', $run->payroll_month) . '-' . str_pad($item->employee_id, 4, '0', STR_PAD_LEFT);

            Payslip::firstOrCreate(
                [
                    'payroll_item_id' => $item->id,
                    'employee_id' => $item->employee_id,
                ],
                [
                    'payslip_number' => $payslipNumber,
                    'payroll_month' => $run->payroll_month,
                    'published_at' => now(),
                    'is_viewed' => false,
                ]
            );
            $count++;
        }

        $run->update(['status' => 'published']);

        AuditLog::log('payroll.published', 'PayrollRun', $run->id, "Published {$count} payslips for {$run->payroll_month}.");

        return response()->json([
            'status' => 'success',
            'message' => "Published {$count} employee payslips successfully.",
        ]);
    }

    /**
     * Employee's own payslips list
     */
    public function myPayslips(Request $request)
    {
        $employee = $request->user()->employee;
        if (!$employee) {
            return response()->json(['message' => 'Employee profile not found.'], 404);
        }

        $payslips = Payslip::with(['payrollItem.components', 'payrollItem.payrollRun'])
            ->where('employee_id', $employee->id)
            ->latest('payroll_month')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $payslips,
        ]);
    }

    /**
     * Download or view single payslip voucher details
     */
    public function downloadPayslip(Request $request, $id)
    {
        $user = $request->user();
        $payslip = Payslip::with([
            'payrollItem.components',
            'payrollItem.payrollRun',
            'employee.user',
            'employee.department',
            'employee.designation',
            'employee.bankAccount',
        ])->findOrFail($id);

        // Security check: employee can only view own payslip unless admin
        if (!$user->hasRole(['Super Admin', 'HR Admin', 'Payroll Manager', 'Payroll Approver'])) {
            if ($user->employee && $user->employee->id !== $payslip->employee_id) {
                return response()->json(['message' => 'Unauthorized access to payslip.'], 403);
            }
        }

        $payslip->update(['is_viewed' => true]);

        return response()->json([
            'status' => 'success',
            'data' => $payslip,
        ]);
    }
}
