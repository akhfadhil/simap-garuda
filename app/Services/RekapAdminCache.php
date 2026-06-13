<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class RekapAdminCache
{
    private const AGGREGATE_KEYS = 'rekap_admin_aggregate_keys';

    private const CHART_KEYS = 'rekap_admin_chart_keys';

    private const DASHBOARD_KEYS = 'rekap_dashboard_summary_keys';

    public static function rememberAggregate(string $jenis, ?int $dapilId, callable $callback, array $scope = []): array
    {
        $key = self::aggregateKey($jenis, $dapilId, $scope);

        if (self::ttlSeconds() <= 0) {
            return $callback();
        }

        $keys = Cache::get(self::AGGREGATE_KEYS, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::AGGREGATE_KEYS, $keys);
        }

        return Cache::remember($key, now()->addSeconds(self::ttlSeconds()), $callback);
    }

    public static function rememberChart(array $parts, callable $callback): array
    {
        $key = 'rekap_admin_chart_'.md5(json_encode($parts));

        if (self::ttlSeconds() <= 0) {
            return $callback();
        }

        $keys = Cache::get(self::CHART_KEYS, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::CHART_KEYS, $keys);
        }

        return Cache::remember($key, now()->addSeconds(self::ttlSeconds()), $callback);
    }

    public static function rememberDashboardSummary(array $parts, callable $callback): array
    {
        $key = 'rekap_dashboard_summary_'.md5(json_encode($parts));

        if (self::ttlSeconds() <= 0) {
            return $callback();
        }

        $keys = Cache::get(self::DASHBOARD_KEYS, []);

        if (! in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::forever(self::DASHBOARD_KEYS, $keys);
        }

        return Cache::remember($key, now()->addSeconds(self::ttlSeconds()), $callback);
    }

    public static function flushAggregate(): void
    {
        foreach (Cache::get(self::AGGREGATE_KEYS, []) as $key) {
            Cache::forget($key);
        }

        foreach (Cache::get(self::CHART_KEYS, []) as $key) {
            Cache::forget($key);
        }

        foreach (Cache::get(self::DASHBOARD_KEYS, []) as $key) {
            Cache::forget($key);
        }

        Cache::forget(self::AGGREGATE_KEYS);
        Cache::forget(self::CHART_KEYS);
        Cache::forget(self::DASHBOARD_KEYS);
    }

    private static function aggregateKey(string $jenis, ?int $dapilId, array $scope = []): string
    {
        return 'rekap_admin_aggregate_'.$jenis.'_'.($dapilId ?: 'all').'_'.md5(json_encode($scope));
    }

    private static function ttlSeconds(): int
    {
        return max(0, (int) config('rekap.cache_ttl_seconds', 30));
    }
}
