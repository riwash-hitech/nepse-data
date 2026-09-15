<?php

namespace App\Support;

/**
 * Minimal User-Agent parser — covers the common browsers/platforms/device
 * types well enough for an activity log, without pulling in a full UA
 * parsing package. Order matters below (e.g. Edge/Opera checked before
 * Chrome, since both include "Chrome" in their UA string).
 */
class DeviceParser
{
    public static function parse(?string $userAgent): array
    {
        $ua = (string) $userAgent;

        return [
            'device_type' => self::deviceType($ua),
            'browser'     => self::browser($ua),
            'platform'    => self::platform($ua),
        ];
    }

    private static function deviceType(string $ua): string
    {
        if (preg_match('/iPad|Tablet(?!.*Mobile)/i', $ua)) {
            return 'tablet';
        }
        if (preg_match('/Mobi|Android.*Mobile|iPhone|iPod/i', $ua)) {
            return 'mobile';
        }
        return 'desktop';
    }

    private static function browser(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/Edg\//i', $ua)               => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $ua)         => 'Opera',
            (bool) preg_match('/YaBrowser/i', $ua)           => 'Yandex',
            (bool) preg_match('/Brave/i', $ua)               => 'Brave',
            (bool) preg_match('/CriOS/i', $ua)               => 'Chrome (iOS)',
            (bool) preg_match('/Chrome\//i', $ua)            => 'Chrome',
            (bool) preg_match('/FxiOS/i', $ua)               => 'Firefox (iOS)',
            (bool) preg_match('/Firefox\//i', $ua)           => 'Firefox',
            (bool) preg_match('/Version\/.*Safari/i', $ua)   => 'Safari',
            (bool) preg_match('/MSIE|Trident/i', $ua)        => 'Internet Explorer',
            default                                          => 'Other',
        };
    }

    private static function platform(string $ua): string
    {
        return match (true) {
            (bool) preg_match('/Windows NT 10/i', $ua)  => 'Windows 10/11',
            (bool) preg_match('/Windows/i', $ua)         => 'Windows',
            (bool) preg_match('/iPhone|iPad|iPod/i', $ua) => 'iOS',
            (bool) preg_match('/Mac OS X/i', $ua)        => 'macOS',
            (bool) preg_match('/Android/i', $ua)         => 'Android',
            (bool) preg_match('/Linux/i', $ua)           => 'Linux',
            default                                       => 'Other',
        };
    }
}
