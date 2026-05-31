<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketSummary extends Model
{
    protected $fillable = [
        'date', 'nepse_index', 'nepse_change', 'nepse_change_pct',
        'sensitive_index', 'float_index',
        'total_turnover', 'total_volume', 'total_transactions',
        'scrip_traded', 'positive_count', 'negative_count', 'unchanged_count',
        'top_gainers', 'top_losers', 'most_active',
    ];

    protected $casts = [
        'date' => 'date',
        'nepse_index' => 'decimal:2',
        'nepse_change' => 'decimal:2',
        'nepse_change_pct' => 'decimal:4',
        'sensitive_index' => 'decimal:2',
        'float_index' => 'decimal:2',
        'total_turnover' => 'decimal:2',
        'total_volume' => 'integer',
        'total_transactions' => 'integer',
        'top_gainers' => 'array',
        'top_losers' => 'array',
        'most_active' => 'array',
    ];

    public static function latest(): ?self
    {
        return static::orderByDesc('date')->first();
    }
}
