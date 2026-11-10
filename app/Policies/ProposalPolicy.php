<?php

namespace App\Policies;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Models\Proposal;
use App\Models\User;

class ProposalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function reviewAny(User $user): bool
    {
        return $user->is_active && in_array($user->role, [UserRole::Broker, UserRole::Administrator], true);
    }

    public function review(User $user, Proposal $proposal): bool
    {
        return $this->reviewAny($user) && $proposal->status === ProposalStatus::Submitted;
    }

    public function create(User $user): bool
    {
        return $user->is_active && $user->role === UserRole::Customer;
    }

    public function view(User $user, Proposal $proposal): bool
    {
        if (! $user->is_active) {
            return false;
        }
        if ($user->role === UserRole::Administrator) {
            return true;
        }
        if ($user->role === UserRole::Broker) {
            return $proposal->status !== ProposalStatus::Draft;
        }

        return $proposal->user_id === $user->id;
    }

    public function update(User $user, Proposal $proposal): bool
    {
        return $this->create($user) && $proposal->user_id === $user->id && $proposal->status === ProposalStatus::Draft;
    }

    public function delete(User $user, Proposal $proposal): bool
    {
        return $this->update($user, $proposal);
    }

    public function confirm(User $user, Proposal $proposal): bool
    {
        return $this->create($user) && $proposal->user_id === $user->id && in_array($proposal->status, [ProposalStatus::Approved, ProposalStatus::Contracted], true);
    }

    public function submit(User $user, Proposal $proposal): bool
    {
        return $this->update($user, $proposal);
    }
}
