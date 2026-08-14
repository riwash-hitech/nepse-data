<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioHolding extends Model
{
    protected $fillable = ['user_id', 'stock_id', 'quantity', 'avg_cost'];

    protected $casts = [
        'quantity' => 'integer',
        'avg_cost' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
