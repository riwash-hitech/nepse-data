<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockPrice extends Model
{
    protected $fillable = [
        'stock_id', 'date', 'open', 'high', 'low', 'close',
        'previous_close', 'volume', 'turnover', 'transactions',
        'change', 'change_percent', 'vwap',
    ];

    protected $casts = [
        'date' => 'date',
        'open' => 'decimal:2',
        'high' => 'decimal:2',
        'low' => 'decimal:2',
        'close' => 'decimal:2',
        'previous_close' => 'decimal:2',
        'change' => 'decimal:2',
        'change_percent' => 'decimal:4',
        'vwap' => 'decimal:2',
        'volume' => 'integer',
        'turnover' => 'decimal:2',
        'transactions' => 'integer',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
