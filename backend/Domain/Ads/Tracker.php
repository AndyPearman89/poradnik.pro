<?php

namespace Poradnik\Platform\Domain\Ads;

use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Infrastructure\Database\Migrator;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

final class Tracker
{
    /**
     * @return int|WP_Error
     */
    public static function trackImpression(int $campaignId, int $slotId = 0, string $source = '', string $userIp = '')
    {
        return self::track('ad_impressions', 'poradnik_platform_ad_impression_tracked', $campaignId, $slotId, $source, $userIp, false);
    }

    /**
     * @return int|WP_Error
     */
    public static function trackClick(int $campaignId, int $slotId = 0, string $source = '', string $userIp = '')
    {
        return self::track('ad_clicks', 'poradnik_platform_ad_click_tracked', $campaignId, $slotId, $source, $userIp);
    }

    /**
     * @return int|WP_Error
     */
    private static function track(string $tableSuffix, string $eventHook, int $campaignId, int $slotId, string $source, string $userIp, bool $trackIp = true)
    {
        global $wpdb;

        if ($campaignId < 1) {
            return new WP_Error('poradnik_invalid_campaign_id', 'Parameter campaign_id is required.', ['status' => 400]);
        }

        $table = Migrator::tableName($tableSuffix);
        $now = current_time('mysql', true);

        $data = [
            'campaign_id' => $campaignId,
            'slot_id'     => $slotId > 0 ? $slotId : 0,
            'source'      => sanitize_text_field($source),
            'created_at'  => $now,
            'updated_at'  => $now,
        ];
        $format = ['%d', '%d', '%s', '%s', '%s'];

        if ($trackIp) {
            $data['user_ip'] = sanitize_text_field($userIp);
            $format[]        = '%s';
        }

        $inserted = $wpdb->insert($table, $data, $format);

        if ($inserted !== 1) {
            return new WP_Error('poradnik_ad_tracking_insert_failed', 'Could not store ad tracking event.', ['status' => 500]);
        }

        EventLogger::dispatch($eventHook, ['campaign_id' => $campaignId, 'slot_id' => $slotId, 'source' => sanitize_text_field($source)]);

        return (int) $wpdb->insert_id;
    }
}
