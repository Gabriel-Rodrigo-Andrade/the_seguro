<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    protected $fillable = ['name', 'description', 'annual_price', 'is_active'];

    protected function casts(): array
    {
        return ['annual_price' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'plan_service')->withTimestamps();
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->where('is_active', true)->whereHas('services')->whereDoesntHave('services', fn (Builder $services) => $services->where('is_active', false));
    }

    public function isAvailable(): bool
    {
        return self::whereKey($this->id)->available()->exists();
    }
}
