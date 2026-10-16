<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItemComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'component_name',
        'component_code',
        'component_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function payrollItem()
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
