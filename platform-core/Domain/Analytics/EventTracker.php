<?php

namespace Poradnik\Platform\Domain\Analytics;

if (! defined('ABSPATH')) {
    exit;
}

final class EventTracker
{
    public static function trackAffiliateClick(int $productId, int $postId = 0, string $source = ''): void
    {
        $settings = AnalyticsService::getSettings();

        if (! (bool) ($settings['track_affiliate_clicks'] ?? true)) {
            return;
        }

        do_action('poradnik_analytics_affiliate_click', [
            'product_id' => $productId,
            'post_id' => $postId,
            'source' => $source,
        ]);
    }

    public static function trackAdClick(int $campaignId, int $slotId = 0, string $source = ''): void
    {
        $settings = AnalyticsService::getSettings();

        if (! (bool) ($settings['track_ad_clicks'] ?? true)) {
            return;
        }

        do_action('poradnik_analytics_ad_click', [
            'campaign_id' => $campaignId,
            'slot_id' => $slotId,
            'source' => $source,
        ]);
    }

    public static function trackAdImpression(int $campaignId, int $slotId = 0, string $source = ''): void
    {
        $settings = AnalyticsService::getSettings();

        if (! (bool) ($settings['track_ad_impressions'] ?? true)) {
            return;
        }

        do_action('poradnik_analytics_ad_impression', [
            'campaign_id' => $campaignId,
            'slot_id' => $slotId,
            'source' => $source,
        ]);
    }
}
