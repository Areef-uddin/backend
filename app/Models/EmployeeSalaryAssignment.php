<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary_structure_id',
        'base_monthly_ctc',
        'basic_salary',
        'hra',
        'special_allowance',
        'pf_employee',
        'pf_employer',
        'esic_employee',
        'esic_employer',
        'professional_tax',
        'tds_estimate',
        'gross_salary',
        'net_salary',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'base_monthly_ctc' => 'float',
        'basic_salary' => 'float',
        'hra' => 'float',
        'special_allowance' => 'float',
        'pf_employee' => 'float',
        'pf_employer' => 'float',
        'esic_employee' => 'float',
        'esic_employer' => 'float',
        'professional_tax' => 'float',
        'tds_estimate' => 'float',
        'gross_salary' => 'float',
        'net_salary' => 'float',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function structure()
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }
}
