<?php

namespace PPAM\Analytics;

if (!defined('ABSPATH')) {
    exit;
}

class Stats
{
    public static function init(): void
    {
        add_action('template_redirect', [self::class, 'handleClick']);
    }

    public static function addImpression(int $campaignId): void
    {
        $impressions = (int) get_post_meta($campaignId, '_ppam_impressions', true);
        update_post_meta($campaignId, '_ppam_impressions', $impressions + 1);
    }

    public static function addClick(int $campaignId): void
    {
        $clicks = (int) get_post_meta($campaignId, '_ppam_clicks', true);
        update_post_meta($campaignId, '_ppam_clicks', $clicks + 1);
    }

    public static function getCtr(int $campaignId): float
    {
        $impressions = (int) get_post_meta($campaignId, '_ppam_impressions', true);
        $clicks = (int) get_post_meta($campaignId, '_ppam_clicks', true);

        if ($impressions <= 0) {
            return 0.0;
        }

        return round(($clicks / $impressions) * 100, 2);
    }

    public static function handleClick(): void
    {
        if (!isset($_GET['ppam_click'], $_GET['campaign'], $_GET['to'])) {
            return;
        }

        $campaignId = max(0, (int) wp_unslash($_GET['campaign']));
        $targetUrl = esc_url_raw(rawurldecode((string) wp_unslash($_GET['to'])));

        if ($campaignId <= 0 || $targetUrl === '') {
            return;
        }

        self::addClick($campaignId);
        wp_safe_redirect($targetUrl);
        exit;
    }

    public static function buildTrackedUrl(int $campaignId, string $targetUrl): string
    {
        return add_query_arg([
            'ppam_click' => 1,
            'campaign' => $campaignId,
            'to' => rawurlencode($targetUrl),
        ], home_url('/'));
    }
}
