<?php

namespace App\Policies;

use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TimesheetPolicy
{
    public function viewAny(User $user): bool
    {
        // Anyone logged in can view the timesheet lists
        return true; 
    }
    public function view(User $user, Timesheet $timesheet): bool
    {
        return true;
    }
    public function create(User $user): bool
    {
        // Anyone logged in can log new timesheets
        return true;
    }
    public function update(User $user, Timesheet $timesheet): bool
    {
        // Only administrators can edit timesheets
        return $user->isAdmin();
    }
    public function delete(User $user, Timesheet $timesheet): bool
    {
        // Only administrators can delete timesheet records
        return $user->isAdmin();
    }
    public function forceDelete(User $user, Timesheet $timesheet): bool
    {
        return $user->isAdmin();
    }
}