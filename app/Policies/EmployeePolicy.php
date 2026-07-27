<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeePolicy //was when i tried to add admin righrts
{
   
    public function viewAny(User $user): bool
    {
        // Everyone can view the list of employees to associate them with timesheets
        return true;
    }

    public function view(User $user, Employee $employee): bool
    {
        // Everyone can view an individual employee record
        return true;
    }
  
    public function create(User $user): bool
    {
        // Only administrators can add new employees
        return $user->isAdmin();
    }

    public function update(User $user, Employee $employee): bool
    {
        // Only administrators can edit employee details
        return $user->isAdmin();
    }

    public function delete(User $user, Employee $employee): bool
    {
        // Only administrators can delete employees
        return $user->isAdmin();
    }
    
    public function restore(User $user, Employee $employee): bool
    {
        return $user->isAdmin();
    }
    public function forceDelete(User $user, Employee $employee): bool 
    {
        return $user->isAdmin();
    }
} 

