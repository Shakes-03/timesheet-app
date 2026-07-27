<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Trade;
use App\Models\Timesheet;

class Employee extends Model
{
  
    protected $guarded = []; // Allow mass assignment for all attributes
    public function trade(): BelongsTo //links employee to the trade table so that when you select a trade it will automatically populate the rates and other fields in the timesheet
    {
        return $this->belongsTo(Trade::class, 'trade_id');
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }
}