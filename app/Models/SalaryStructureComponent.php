<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryStructureComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'salary_structure_id',
        'salary_component_id',
        'default_amount',
        'default_percentage',
    ];

    protected $casts = [
        'default_amount' => 'float',
        'default_percentage' => 'float',
    ];

    public function structure()
    {
        return $this->belongsTo(SalaryStructure::class, 'salary_structure_id');
    }

    public function component()
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }
}
