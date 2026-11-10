<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['quoted_amount' => 'decimal:2', 'status' => ProposalStatus::class, 'services_snapshot' => 'array', 'submitted_at' => 'datetime'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === UserRole::Customer) {
            $query->where('user_id', $user->id);

            return;
        }
        if ($user->role === UserRole::Broker) {
            $query->where('status', '!=', ProposalStatus::Draft->value);
        }
    }
}
