<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\InsuranceContract;
use App\Models\User;

class InsuranceContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, InsuranceContract $contract): bool
    {
        return $user->is_active && ($user->role !== UserRole::Customer || $contract->proposal->user_id === $user->id);
    }
}
