<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Floorsheet extends Model
{
    protected $fillable = [
        'stock_id', 'date', 'contract_id',
        'buyer_broker', 'seller_broker',
        'quantity', 'rate', 'amount',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'integer',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
