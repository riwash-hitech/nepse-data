<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortfolioTransaction extends Model
{
    protected $fillable = ['user_id', 'stock_id', 'type', 'quantity', 'rate', 'txn_date', 'realized_gain', 'remarks'];

    protected $casts = [
        'quantity'      => 'integer',
        'rate'          => 'decimal:4',
        'txn_date'      => 'date',
        'realized_gain' => 'decimal:2',
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
