<?php

namespace App\Models;

use App\Enums\ServiceRequestStatus;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['status' => ServiceRequestStatus::class, 'completed_at' => 'datetime'];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(InsuranceContract::class, 'insurance_contract_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === UserRole::Customer) {
            $query->whereHas('contract.proposal', fn ($proposal) => $proposal->where('user_id', $user->id));
        }
    }

    public function contractedService(): array
    {
        return collect($this->contract->proposal->services_snapshot)->firstWhere('id', $this->service_id) ?? [];
    }
}
