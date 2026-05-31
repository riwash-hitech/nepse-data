<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Signal extends Model
{
    protected $fillable = [
        'stock_id', 'date', 'signal_type', 'confidence', 'reasons',
        'entry_min', 'entry_max', 'stop_loss', 'target_1', 'target_2',
        'risk_reward', 'price_at_signal', 'rsi_value', 'macd_value',
        'volume_at_signal', 'is_active', 'expires_at',
    ];

    protected $casts = [
        'date' => 'date',
        'reasons' => 'array',
        'confidence' => 'integer',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'entry_min' => 'decimal:2',
        'entry_max' => 'decimal:2',
        'stop_loss' => 'decimal:2',
        'target_1' => 'decimal:2',
        'target_2' => 'decimal:2',
        'risk_reward' => 'decimal:4',
        'price_at_signal' => 'decimal:2',
        'rsi_value' => 'decimal:4',
        'macd_value' => 'decimal:4',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function scopeBuy(Builder $query): Builder
    {
        return $query->where('signal_type', 'BUY');
    }

    public function scopeSell(Builder $query): Builder
    {
        return $query->where('signal_type', 'SELL');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeHighConfidence(Builder $query, int $threshold = 70): Builder
    {
        return $query->where('confidence', '>=', $threshold);
    }

    public function getSignalColorAttribute(): string
    {
        return match ($this->signal_type) {
            'BUY'  => 'green',
            'SELL' => 'red',
            default => 'yellow',
        };
    }
}
