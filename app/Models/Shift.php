<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'grace_period_minutes',
        'half_day_hours',
        'full_day_hours',
        'is_night_shift',
        'is_active',
    ];

    protected $casts = [
        'is_night_shift' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function employees()
    {
        return $this->belongsToMany(Employee::class, 'employee_shifts')
                    ->withPivot('effective_from', 'effective_to')
                    ->withTimestamps();
    }
}
