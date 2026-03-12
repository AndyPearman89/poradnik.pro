<?php

namespace Poradnik\Platform\Domain\Affiliate;

use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Infrastructure\Database\Migrator;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

final class ClickTracker
{
    /**
     * @return int|WP_Error
     */
    public static function track(int $productId, int $postId = 0, string $source = '', string $referrer = '', string $userIp = '')
    {
        global $wpdb;

        if ($productId < 1) {
            return new WP_Error('poradnik_invalid_product_id', 'Parameter product_id is required.', ['status' => 400]);
        }

        $now = current_time('mysql', true);
        $table = Migrator::tableName('affiliate_clicks');
        $normalizedPostId = $postId > 0 ? $postId : 0;

        $inserted = $wpdb->insert(
            $table,
            [
                'product_id' => $productId,
                'post_id' => $normalizedPostId,
                'source' => sanitize_text_field($source),
                'referrer' => esc_url_raw($referrer),
                'user_ip' => sanitize_text_field($userIp),
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                '%d',
                '%d',
                '%s',
                '%s',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($inserted !== 1) {
            return new WP_Error('poradnik_affiliate_click_insert_failed', 'Could not store affiliate click.', ['status' => 500]);
        }

        EventLogger::dispatch(
            'poradnik_platform_affiliate_click_tracked',
            [
                'product_id' => $productId,
                'post_id' => $normalizedPostId,
                'source' => sanitize_text_field($source),
            ]
        );

        return (int) $wpdb->insert_id;
    }
}
