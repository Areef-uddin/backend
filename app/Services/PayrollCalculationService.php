<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\PayrollRun;
use App\Models\PayrollItem;
use App\Models\PayrollItemComponent;
use App\Models\AttendanceRecord;
use App\Models\LeaveRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollCalculationService
{
    /**
     * Calculate payroll for a given year and month.
     */
    public function calculateMonthlyPayroll(PayrollRun $payrollRun): array
    {
        $year = $payrollRun->year;
        $month = $payrollRun->month;
        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();
        $totalDaysInMonth = $startDate->daysInMonth;

        $employees = Employee::with(['user', 'department', 'designation', 'currentSalaryAssignment'])
            ->where('employment_status', 'active')
            ->get();

        $processedItems = [];
        $totalGross = 0;
        $totalDeductions = 0;
        $totalNetPay = 0;

        DB::beginTransaction();

        try {
            // Delete existing items if recalculating draft
            PayrollItem::where('payroll_run_id', $payrollRun->id)->delete();

            foreach ($employees as $employee) {
                $salaryAssignment = $employee->currentSalaryAssignment;
                if (!$salaryAssignment) {
                    continue;
                }

                // 1. Calculate Attendance & LOP
                $attendanceRecords = AttendanceRecord::where('employee_id', $employee->id)
                    ->whereBetween('work_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                    ->get();

                $presentDays = $attendanceRecords->whereIn('status', ['present', 'late'])->count();
                $halfDays = $attendanceRecords->where('status', 'half_day')->count();
                $effectivePresentDays = $presentDays + ($halfDays * 0.5);

                $approvedLeaves = LeaveRequest::where('employee_id', $employee->id)
                    ->where('status', 'approved')
                    ->where(function ($q) use ($startDate, $endDate) {
                        $q->whereBetween('start_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
                          ->orWhereBetween('end_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);
                    })
                    ->sum('total_days');

                // Default standard working month (assumed weekends off if full attendance or standard calendar)
                // If records exist, calculate absent days as LOP
                $recordedDays = $attendanceRecords->count();
                $absentDays = $attendanceRecords->where('status', 'absent')->count();
                
                // Payable days calculation:
                $lopDays = (float) $absentDays;
                $payableDays = max(0, $totalDaysInMonth - $lopDays);

                // 2. Earnings Components
                $basic = (float) $salaryAssignment->basic_salary;
                $hra = (float) $salaryAssignment->hra;
                $specialAllowance = (float) $salaryAssignment->special_allowance;
                $overtimePay = 0.00;
                $grossEarnings = $basic + $hra + $specialAllowance + $overtimePay;

                // 3. Loss of Pay (LOP) Deduction
                $perDayRate = $totalDaysInMonth > 0 ? ($grossEarnings / $totalDaysInMonth) : 0;
                $lopDeduction = round($perDayRate * $lopDays, 2);

                // 4. Statutory & Other Deductions
                $pfDeduction = (float) $salaryAssignment->pf_employee;
                $esicDeduction = (float) $salaryAssignment->esic_employee;
                $ptDeduction = (float) $salaryAssignment->professional_tax;
                $tdsDeduction = (float) $salaryAssignment->tds_estimate;
                $otherDeductions = 0.00;

                $totalEmployeeDeductions = round(
                    $lopDeduction + $pfDeduction + $esicDeduction + $ptDeduction + $tdsDeduction + $otherDeductions,
                    2
                );

                $netPay = max(0, round($grossEarnings - $totalEmployeeDeductions, 2));

                // 5. Create Payroll Item
                $payrollItem = PayrollItem::create([
                    'payroll_run_id' => $payrollRun->id,
                    'employee_id' => $employee->id,
                    'total_days_in_month' => $totalDaysInMonth,
                    'payable_days' => $payableDays,
                    'present_days' => $effectivePresentDays,
                    'leave_days' => (float) $approvedLeaves,
                    'lop_days' => $lopDays,
                    'basic_pay' => $basic,
                    'hra' => $hra,
                    'special_allowance' => $specialAllowance,
                    'overtime_pay' => $overtimePay,
                    'gross_earnings' => $grossEarnings,
                    'lop_deduction' => $lopDeduction,
                    'pf_deduction' => $pfDeduction,
                    'esic_deduction' => $esicDeduction,
                    'pt_deduction' => $ptDeduction,
                    'tds_deduction' => $tdsDeduction,
                    'other_deductions' => $otherDeductions,
                    'total_deductions' => $totalEmployeeDeductions,
                    'net_pay' => $netPay,
                    'payment_status' => 'pending',
                ]);

                // 6. Store Item Breakdown Components
                $components = [
                    ['name' => 'Basic Salary', 'code' => 'BASIC', 'type' => 'earning', 'amount' => $basic],
                    ['name' => 'House Rent Allowance (HRA)', 'code' => 'HRA', 'type' => 'earning', 'amount' => $hra],
                    ['name' => 'Special Allowance', 'code' => 'SPL_ALLOW', 'type' => 'earning', 'amount' => $specialAllowance],
                ];

                if ($lopDeduction > 0) {
                    $components[] = ['name' => 'Loss of Pay (LOP)', 'code' => 'LOP', 'type' => 'deduction', 'amount' => $lopDeduction];
                }
                if ($pfDeduction > 0) {
                    $components[] = ['name' => 'Provident Fund (PF)', 'code' => 'PF', 'type' => 'deduction', 'amount' => $pfDeduction];
                }
                if ($esicDeduction > 0) {
                    $components[] = ['name' => 'ESIC Deduction', 'code' => 'ESIC', 'type' => 'deduction', 'amount' => $esicDeduction];
                }
                if ($ptDeduction > 0) {
                    $components[] = ['name' => 'Professional Tax (PT)', 'code' => 'PT', 'type' => 'deduction', 'amount' => $ptDeduction];
                }
                if ($tdsDeduction > 0) {
                    $components[] = ['name' => 'Income Tax (TDS)', 'code' => 'TDS', 'type' => 'deduction', 'amount' => $tdsDeduction];
                }

                foreach ($components as $cmp) {
                    PayrollItemComponent::create([
                        'payroll_item_id' => $payrollItem->id,
                        'component_name' => $cmp['name'],
                        'component_code' => $cmp['code'],
                        'component_type' => $cmp['type'],
                        'amount' => $cmp['amount'],
                    ]);
                }

                $totalGross += $grossEarnings;
                $totalDeductions += $totalEmployeeDeductions;
                $totalNetPay += $netPay;
                $processedItems[] = $payrollItem;
            }

            // Update PayrollRun record
            $payrollRun->update([
                'status' => 'calculated',
                'total_employees' => count($processedItems),
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net_pay' => $totalNetPay,
            ]);

            AuditLog::log(
                'payroll.calculated',
                'PayrollRun',
                $payrollRun->id,
                "Calculated payroll for {$payrollRun->payroll_month} with " . count($processedItems) . " employees. Total Net: ₹" . number_format($totalNetPay, 2)
            );

            DB::commit();

            return [
                'success' => true,
                'payroll_run' => $payrollRun->fresh(['items.employee.user', 'items.components']),
                'total_employees' => count($processedItems),
                'total_gross' => $totalGross,
                'total_deductions' => $totalDeductions,
                'total_net_pay' => $totalNetPay,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
