<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_month',
        'year',
        'month',
        'status',
        'total_employees',
        'total_gross',
        'total_deductions',
        'total_net_pay',
        'created_by',
        'approved_by',
        'approved_at',
        'finalized_at',
        'notes',
    ];

    protected $casts = [
        'total_gross' => 'float',
        'total_deductions' => 'float',
        'total_net_pay' => 'float',
        'approved_at' => 'datetime',
        'finalized_at' => 'datetime',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function items()
    {
        return $this->hasMany(PayrollItem::class);
    }
}
