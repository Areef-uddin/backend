<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrackingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'attendance_record_id',
        'started_at',
        'ended_at',
        'status',
        'total_distance_km',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'total_distance_km' => 'float',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function locations()
    {
        return $this->hasMany(EmployeeLocation::class, 'session_id');
    }
}
