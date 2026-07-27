<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trade extends Model
{
    protected $fillable = [ // Allow mass assignment
    'name',
    'description', 
    'rate_type',
    'normal_rate_to_man',
    'flat_overtime_override',
    'leave_rate',
    'enhancement_bonus',
    'total_cc',
    'ctc',
    'admin_fee',
    ];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function getBillingRateAttribute(): float
    {
        return (float) ($this->ctc + $this->admin_fee);
    }
}