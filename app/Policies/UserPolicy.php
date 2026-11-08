<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->is_active && $actor->role === UserRole::Administrator;
    }

    public function view(User $actor, User $user): bool
    {
        return $this->viewAny($actor);
    }

    public function create(User $actor): bool
    {
        return $this->viewAny($actor);
    }

    public function update(User $actor, User $user): bool
    {
        return $this->viewAny($actor);
    }

    public function deactivate(User $actor, User $user): bool
    {
        return $this->viewAny($actor);
    }
}
