<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Timesheet extends Model
{
    // This unlocks the model fields for form submissions
    protected $guarded = [];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}