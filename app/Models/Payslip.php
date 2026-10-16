<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payslip extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'employee_id',
        'payslip_number',
        'payroll_month',
        'file_path',
        'published_at',
        'is_viewed',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_viewed' => 'boolean',
    ];

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
