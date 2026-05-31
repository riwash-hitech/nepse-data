<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Indicator extends Model
{
    protected $fillable = [
        'stock_id', 'date',
        'sma_20', 'sma_50', 'sma_200',
        'ema_12', 'ema_26', 'ema_50',
        'rsi_14',
        'macd', 'macd_signal', 'macd_histogram',
        'bb_upper', 'bb_middle', 'bb_lower',
        'atr_14',
        'support_1', 'support_2', 'resistance_1', 'resistance_2',
        'pivot',
    ];

    protected $casts = [
        'date' => 'date',
        'sma_20' => 'decimal:4',
        'sma_50' => 'decimal:4',
        'sma_200' => 'decimal:4',
        'ema_12' => 'decimal:4',
        'ema_26' => 'decimal:4',
        'ema_50' => 'decimal:4',
        'rsi_14' => 'decimal:4',
        'macd' => 'decimal:4',
        'macd_signal' => 'decimal:4',
        'macd_histogram' => 'decimal:4',
        'bb_upper' => 'decimal:4',
        'bb_middle' => 'decimal:4',
        'bb_lower' => 'decimal:4',
        'atr_14' => 'decimal:4',
        'support_1' => 'decimal:2',
        'support_2' => 'decimal:2',
        'resistance_1' => 'decimal:2',
        'resistance_2' => 'decimal:2',
        'pivot' => 'decimal:2',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
