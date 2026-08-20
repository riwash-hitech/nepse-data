<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class Activity
{
    public static function log(?User $user, string $action, ?string $description = null): void
    {
        ActivityLog::create([
            'user_id'     => $user?->id,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => Request::ip(),
            'user_agent'  => substr((string) Request::userAgent(), 0, 255),
            'created_at'  => now(),
        ]);
    }
}
