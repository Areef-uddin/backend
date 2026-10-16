<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'employee_code',
        'department_id',
        'designation_id',
        'manager_id',
        'joining_date',
        'confirmation_date',
        'employment_status',
        'employment_type',
        'work_location',
        'emergency_contact',
        'emergency_phone',
        'dob',
        'gender',
        'marital_status',
        'blood_group',
        'address',
        'city',
        'state',
        'postal_code',
        'pan_number',
        'aadhaar_number',
        'uan_number',
        'esic_number',
    ];

    protected $casts = [
        'joining_date' => 'date',
        'confirmation_date' => 'date',
        'dob' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function bankAccount()
    {
        return $this->hasOne(EmployeeBankAccount::class)->where('is_primary', true);
    }

    public function bankAccounts()
    {
        return $this->hasMany(EmployeeBankAccount::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function shifts()
    {
        return $this->belongsToMany(Shift::class, 'employee_shifts')
                    ->withPivot('effective_from', 'effective_to', 'is_active')
                    ->withTimestamps();
    }


    public function currentShift()
    {
        return $this->shifts()->latest('employee_shifts.created_at')->first();
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function salaryAssignments()
    {
        return $this->hasMany(EmployeeSalaryAssignment::class);
    }

    public function currentSalaryAssignment()
    {
        return $this->hasOne(EmployeeSalaryAssignment::class)->where('is_active', true)->latest('effective_from');
    }

    public function payrollItems()
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function trackingSessions()
    {
        return $this->hasMany(TrackingSession::class);
    }

    public function latestLocation()
    {
        return $this->hasOne(EmployeeLocation::class)->latestOfMany();
    }
}
