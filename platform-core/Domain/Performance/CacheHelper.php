<?php

namespace Poradnik\Platform\Domain\Performance;

if (! defined('ABSPATH')) {
    exit;
}

final class CacheHelper
{
    public static function purgeOnPostSave(int $postId): void
    {
        if (wp_is_post_revision($postId)) {
            return;
        }

        do_action('poradnik_cache_purge_post', $postId);

        if (function_exists('wp_cache_delete')) {
            wp_cache_delete($postId, 'posts');
            wp_cache_delete($postId, 'post_meta');
        }

        if (function_exists('w3tc_pgcache_flush_post')) {
            w3tc_pgcache_flush_post($postId);
        }

        if (function_exists('wpfc_clear_post_cache_by_id')) {
            wpfc_clear_post_cache_by_id($postId);
        }

        if (function_exists('rocket_clean_post')) {
            rocket_clean_post($postId);
        }
    }

    public static function addCdnReadinessHeaders(): void
    {
        if (is_admin() || is_user_logged_in()) {
            return;
        }

        if (! headers_sent()) {
            header('Vary: Accept-Encoding');
            header('Cache-Control: public, max-age=3600, s-maxage=86400');
        }
    }

    /**
     * @param string $key
     * @param mixed $data
     * @param int $expiry seconds
     * @return bool
     */
    public static function set(string $key, $data, int $expiry = 3600): bool
    {
        return (bool) set_transient('poradnik_cache_' . md5($key), $data, $expiry);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function get(string $key)
    {
        return get_transient('poradnik_cache_' . md5($key));
    }

    public static function delete(string $key): void
    {
        delete_transient('poradnik_cache_' . md5($key));
    }
}
