<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\Timesheet;
use App\Models\User;
use App\Policies\EmployeePolicy;
use App\Policies\TimesheetPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }
    public function boot(): void
    {
        // Register security policies
        Gate::policy(Timesheet::class, TimesheetPolicy::class);
        Gate::policy(Employee::class, EmployeePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
