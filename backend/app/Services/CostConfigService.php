<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * CostConfigService
 *
 * Source of truth for runtime cost configuration.
 * Reads from `cost_configs` table (key => json value) and falls back to env()
 * Caches DB reads for short time to avoid DB pressure.
 */
class CostConfigService
{
    protected string $cachePrefix = 'cost_config:';
    protected int $cacheTtl; // seconds

    public function __construct()
    {
        $this->cacheTtl = (int) env('COST_CONFIG_CACHE_TTL', 300);
    }

    /**
     * Get a config item by key. If found in DB, decode JSON value and return.
     * If not found, return $default (can be scalar or array).
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->cachePrefix . $key;

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($key, $default) {
            try {
                $row = DB::table('cost_configs')->where('key', $key)->where('active', true)->first();
                if ($row) {
                    $val = $row->value;
                    if (is_string($val)) {
                        $decoded = json_decode($val, true);
                        return $decoded === null && json_last_error() !== JSON_ERROR_NONE ? $val : $decoded;
                    }
                    return $val;
                }
            } catch (\Throwable $e) {
                // DB might not exist in some envs — ignore and fall back to env/default.
            }

            // fallback to environment variable (uppercase key)
            $envKey = strtoupper($key);
            $envVal = env($envKey);
            if ($envVal !== null) {
                // Try decode JSON-like values or numeric
                $decoded = json_decode($envVal, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
                // try numeric
                if (is_numeric($envVal)) {
                    return strpos($envVal, '.') !== false ? (float)$envVal : (int)$envVal;
                }
                return $envVal;
            }

            return $default;
        });
    }

    /**
     * Invalidate a single key
     */
    public function forget(string $key): void
    {
        Cache::forget($this->cachePrefix . $key);
    }
}
