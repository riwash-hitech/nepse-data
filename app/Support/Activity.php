<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

class Activity
{
    public static function log(?User $user, string $action, ?string $description = null): void
    {
        $ip     = Request::ip();
        $geo    = GeoLookup::lookup($ip);
        $device = DeviceParser::parse(Request::userAgent());

        ActivityLog::create([
            'user_id'     => $user?->id,
            'action'      => $action,
            'description' => $description,
            'ip_address'  => $ip,
            'country'     => $geo['country'],
            'city'        => $geo['city'],
            'latitude'    => $geo['latitude'],
            'longitude'   => $geo['longitude'],
            'user_agent'  => substr((string) Request::userAgent(), 0, 255),
            'device_type' => $device['device_type'],
            'browser'     => $device['browser'],
            'platform'    => $device['platform'],
            'created_at'  => now(),
        ]);
    }
}
