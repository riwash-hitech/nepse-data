<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'action', 'description', 'ip_address', 'user_agent', 'created_at'])]
class ActivityLog extends Model
{
    const UPDATED_AT = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
