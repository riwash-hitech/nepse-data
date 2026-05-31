<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Alert extends Model
{
    protected $fillable = [
        'user_id', 'stock_id', 'condition_type', 'condition_value',
        'notification_channels', 'is_active', 'is_triggered',
        'triggered_at', 'telegram_chat_id',
    ];

    protected $attributes = [
        'notification_channels' => '["mail"]',
    ];

    protected $casts = [
        'notification_channels' => 'array',
        'is_active' => 'boolean',
        'is_triggered' => 'boolean',
        'triggered_at' => 'datetime',
        'condition_value' => 'decimal:4',
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
