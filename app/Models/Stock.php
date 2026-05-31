<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Builder;

class Stock extends Model
{
    protected $fillable = [
        'symbol', 'name', 'sector_id', 'isin', 'listed_shares',
        'face_value', 'paid_up_capital', 'is_active', 'is_bonus_share', 'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_bonus_share' => 'boolean',
        'listed_shares' => 'integer',
        'face_value' => 'decimal:2',
        'paid_up_capital' => 'decimal:2',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(StockPrice::class)->orderByDesc('date');
    }

    public function latestPrice(): HasOne
    {
        return $this->hasOne(StockPrice::class)->latestOfMany('date');
    }

    public function indicators(): HasMany
    {
        return $this->hasMany(Indicator::class)->orderByDesc('date');
    }

    public function latestIndicator(): HasOne
    {
        return $this->hasOne(Indicator::class)->latestOfMany('date');
    }

    public function signals(): HasMany
    {
        return $this->hasMany(Signal::class)->orderByDesc('date');
    }

    public function latestSignal(): HasOne
    {
        return $this->hasOne(Signal::class)->latestOfMany('date');
    }

    public function floorsheets(): HasMany
    {
        return $this->hasMany(Floorsheet::class)->orderByDesc('date');
    }

    public function watchlists(): HasMany
    {
        return $this->hasMany(Watchlist::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function ($q) use ($term) {
            $q->where('symbol', 'like', "%{$term}%")
              ->orWhere('name', 'like', "%{$term}%");
        });
    }
}
