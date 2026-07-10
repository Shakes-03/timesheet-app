<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    // This unlocks the model fields for form submissions
    protected $guarded = [];

    public function timesheets()
    {
        return $this->hasMany(Timesheet::class);
    }
    public function getFullNameAttribute(): string
{
    return "{$this->first_name} {$this->last_name}";
}
}