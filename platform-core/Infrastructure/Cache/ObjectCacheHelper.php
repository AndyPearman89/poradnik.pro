<?php

namespace Poradnik\Platform\Infrastructure\Cache;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Thin wrapper around WordPress object cache with a platform-specific prefix.
 * All keys are automatically namespaced under 'poradnik_platform'.
 */
final class ObjectCacheHelper
{
    private const GROUP = 'poradnik_platform';

    /**
     * Retrieve a cached value.
     *
     * @return mixed|false  The cached value, or false on cache miss.
     */
    public static function get(string $key): mixed
    {
        return wp_cache_get(self::prefixKey($key), self::GROUP);
    }

    /**
     * Store a value in the cache.
     *
     * @param mixed $value
     */
    public static function set(string $key, mixed $value, int $ttl = 300): bool
    {
        return wp_cache_set(self::prefixKey($key), $value, self::GROUP, $ttl);
    }

    /**
     * Delete a single cached entry.
     */
    public static function delete(string $key): bool
    {
        return wp_cache_delete(self::prefixKey($key), self::GROUP);
    }

    /**
     * Retrieve a value from cache, or compute and store it if missing.
     *
     * @param callable(): mixed $callback
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = 300): mixed
    {
        $cached = self::get($key);

        if ($cached !== false) {
            return $cached;
        }

        $value = $callback();
        self::set($key, $value, $ttl);

        return $value;
    }

    private static function prefixKey(string $key): string
    {
        return 'poradnik_' . $key;
    }
}
