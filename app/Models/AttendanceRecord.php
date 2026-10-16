<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'shift_id',
        'work_date',
        'check_in_at',
        'check_out_at',
        'check_in_lat',
        'check_in_lng',
        'check_in_address',
        'check_out_lat',
        'check_out_lng',
        'check_out_address',
        'status',
        'total_minutes',
        'late_minutes',
        'overtime_minutes',
        'is_verified',
        'verification_method',
        'notes',
    ];

    protected $casts = [
        'work_date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
        'check_in_lat' => 'float',
        'check_in_lng' => 'float',
        'check_out_lat' => 'float',
        'check_out_lng' => 'float',
        'is_verified' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public function corrections()
    {
        return $this->hasMany(AttendanceCorrection::class);
    }
}
