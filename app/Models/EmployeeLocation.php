<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'session_id',
        'latitude',
        'longitude',
        'accuracy',
        'speed',
        'battery_level',
        'is_mock',
        'recorded_at',
        'received_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'accuracy' => 'float',
        'speed' => 'float',
        'is_mock' => 'boolean',
        'recorded_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function session()
    {
        return $this->belongsTo(TrackingSession::class, 'session_id');
    }
}
