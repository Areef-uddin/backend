<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'days_per_year', 'is_paid', 'carry_forward', 'description'];

    protected $casts = [
        'is_paid' => 'boolean',
        'carry_forward' => 'boolean',
    ];

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function requests()
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
