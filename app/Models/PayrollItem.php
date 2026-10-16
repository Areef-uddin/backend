<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_run_id',
        'employee_id',
        'total_days_in_month',
        'payable_days',
        'present_days',
        'leave_days',
        'lop_days',
        'basic_pay',
        'hra',
        'special_allowance',
        'overtime_pay',
        'gross_earnings',
        'lop_deduction',
        'pf_deduction',
        'esic_deduction',
        'pt_deduction',
        'tds_deduction',
        'other_deductions',
        'total_deductions',
        'net_pay',
        'payment_status',
        'payment_reference',
        'paid_at',
    ];

    protected $casts = [
        'payable_days' => 'float',
        'present_days' => 'float',
        'leave_days' => 'float',
        'lop_days' => 'float',
        'basic_pay' => 'float',
        'hra' => 'float',
        'special_allowance' => 'float',
        'overtime_pay' => 'float',
        'gross_earnings' => 'float',
        'lop_deduction' => 'float',
        'pf_deduction' => 'float',
        'esic_deduction' => 'float',
        'pt_deduction' => 'float',
        'tds_deduction' => 'float',
        'other_deductions' => 'float',
        'total_deductions' => 'float',
        'net_pay' => 'float',
        'paid_at' => 'datetime',
    ];

    public function payrollRun()
    {
        return $this->belongsTo(PayrollRun::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function components()
    {
        return $this->hasMany(PayrollItemComponent::class);
    }

    public function payslip()
    {
        return $this->hasOne(Payslip::class);
    }
}
