<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'calculation_type',
        'percentage_of',
        'is_taxable',
        'is_statutory',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_statutory' => 'boolean',
    ];

    public function salaryStructures()
    {
        return $this->belongsToMany(SalaryStructure::class, 'salary_structure_components')
                    ->withPivot('default_amount', 'default_percentage')
                    ->withTimestamps();
    }
}
