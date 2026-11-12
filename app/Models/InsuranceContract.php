<?php

namespace App\Models;

use App\Enums\UserRole;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceContract extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['contracted_at' => 'immutable_datetime', 'starts_on' => 'immutable_date', 'ends_on' => 'immutable_date'];
    }

    public function serviceRequests(): HasMany
    {
        return $this->hasMany(ServiceRequest::class);
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->role === UserRole::Customer) {
            $query->whereHas('proposal', fn ($proposal) => $proposal->where('user_id', $user->id));
        }
    }

    public function isActiveAt(?CarbonInterface $instant = null): bool
    {
        $date = ($instant ?? now())->copy()->timezone('America/Sao_Paulo')->toDateString();

        return $date >= $this->starts_on->toDateString() && $date < $this->ends_on->toDateString();
    }
}
