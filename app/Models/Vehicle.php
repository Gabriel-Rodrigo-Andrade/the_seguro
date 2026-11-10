<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['license_plate', 'brand', 'model', 'manufacture_year', 'model_year', 'color'])]
class Vehicle extends Model
{
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->role === UserRole::Administrator) {
            return $query;
        }

        if ($user->role === UserRole::Broker) {
            return $query->whereHas('proposals', fn ($proposals) => $proposals->where('status', '!=', 'draft'));
        }

        return $query->where('user_id', $user->id);
    }

    public static function normalizePlate(string $plate): string
    {
        return strtoupper(str_replace(['-', ' '], '', trim($plate)));
    }

    protected function casts(): array
    {
        return ['manufacture_year' => 'integer', 'model_year' => 'integer'];
    }
}
