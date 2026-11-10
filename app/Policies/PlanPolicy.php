<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Plan;
use App\Models\User;

class PlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Plan $plan): bool
    {
        return $this->viewAny($user) && ($user->role !== UserRole::Customer || $plan->isAvailable());
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::Administrator;
    }

    public function update(User $user, Plan $plan): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $this->create($user);
    }
}
