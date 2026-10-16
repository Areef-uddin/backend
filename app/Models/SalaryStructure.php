<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryStructure extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'description', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function components()
    {
        return $this->belongsToMany(SalaryComponent::class, 'salary_structure_components')
                    ->withPivot('default_amount', 'default_percentage')
                    ->withTimestamps();
    }

    public function assignments()
    {
        return $this->hasMany(EmployeeSalaryAssignment::class);
    }
}
