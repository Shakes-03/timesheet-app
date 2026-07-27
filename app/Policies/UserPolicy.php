<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        // Only administrators can see the Users menu item and list
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        // Only administrators can view individual user details
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        // Only administrators can register new users
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        // Only administrators can edit user details
        return $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        // Only administrators can delete users
        return $user->isAdmin();
    }

    public function restore(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->isAdmin();
    }
}