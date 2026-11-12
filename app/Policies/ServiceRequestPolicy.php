<?php

namespace App\Policies;

use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use App\Models\ServiceRequest;
use App\Models\User;

class ServiceRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::Customer;
    }

    public function view(User $user, ServiceRequest $request): bool
    {
        return $user->is_active && ($user->role !== UserRole::Customer || $request->contract->proposal->user_id === $user->id);
    }

    public function take(User $user, ServiceRequest $request): bool
    {
        return $user->is_active && in_array($user->role, [UserRole::Broker, UserRole::Administrator], true) && $request->status === ServiceRequestStatus::Open;
    }

    public function complete(User $user, ServiceRequest $request): bool
    {
        return $user->is_active && $request->status === ServiceRequestStatus::InProgress && ($user->role === UserRole::Administrator || ($user->role === UserRole::Broker && $request->assigned_to === $user->id));
    }
}
