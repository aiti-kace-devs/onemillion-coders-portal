<?php

if (!function_exists('cache_flexible_ttl')) {
    function cache_flexible_ttl($staleOverride = null, $refreshOverride = null)
    {
        $configTtl = config('cache.flexible_ttl_in_hours');

        $defaultStale = now()->addHour();
        $defaultRefresh = now()->addDay();

        if ($configTtl) {
            $parts = array_map('intval', explode(',', $configTtl));
            if (count($parts) >= 2) {
                $defaultStale = now()->addHours($parts[0]);
                $defaultRefresh = now()->addHours($parts[1]);
            }
        }

        return [
            $staleOverride ?? $defaultStale,
            $refreshOverride ?? $defaultRefresh
        ];
    }
}
