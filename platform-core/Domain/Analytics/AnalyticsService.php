<?php

namespace Poradnik\Platform\Domain\Analytics;

if (! defined('ABSPATH')) {
    exit;
}

final class AnalyticsService
{
    private const OPTION_KEY = 'poradnik_analytics_settings';

    /**
     * @return array<string, mixed>
     */
    public static function getSettings(): array
    {
        $defaults = [
            'ga4_measurement_id' => '',
            'gsc_site_url' => '',
            'track_affiliate_clicks' => true,
            'track_ad_clicks' => true,
            'track_ad_impressions' => true,
            'reports_email' => '',
        ];

        $stored = get_option(self::OPTION_KEY, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        return array_merge($defaults, $stored);
    }

    /**
     * @param array<string, mixed> $settings
     */
    public static function saveSettings(array $settings): void
    {
        $clean = [
            'ga4_measurement_id' => sanitize_text_field((string) ($settings['ga4_measurement_id'] ?? '')),
            'gsc_site_url' => esc_url_raw((string) ($settings['gsc_site_url'] ?? '')),
            'track_affiliate_clicks' => (bool) ($settings['track_affiliate_clicks'] ?? true),
            'track_ad_clicks' => (bool) ($settings['track_ad_clicks'] ?? true),
            'track_ad_impressions' => (bool) ($settings['track_ad_impressions'] ?? true),
            'reports_email' => sanitize_email((string) ($settings['reports_email'] ?? '')),
        ];

        update_option(self::OPTION_KEY, $clean, false);
    }

    public static function renderGa4Snippet(): void
    {
        $settings = self::getSettings();
        $measurementId = (string) ($settings['ga4_measurement_id'] ?? '');

        if ($measurementId === '') {
            return;
        }

        $measurementId = esc_js($measurementId);

        echo "<!-- Google tag (gtag.js) -->\n";
        echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . esc_attr($measurementId) . '"></script>' . "\n";
        echo '<script>' . "\n";
        echo 'window.dataLayer = window.dataLayer || [];' . "\n";
        echo 'function gtag(){dataLayer.push(arguments);}' . "\n";
        echo "gtag('js', new Date());" . "\n";
        echo "gtag('config', '" . $measurementId . "');" . "\n";
        echo '</script>' . "\n";
    }

    public static function renderGa4EventScript(): void
    {
        $settings = self::getSettings();
        $measurementId = (string) ($settings['ga4_measurement_id'] ?? '');

        if ($measurementId === '') {
            return;
        }

        $trackAffiliate = (bool) ($settings['track_affiliate_clicks'] ?? true) ? 'true' : 'false';
        $trackAdClick = (bool) ($settings['track_ad_clicks'] ?? true) ? 'true' : 'false';
        $trackAdImpression = (bool) ($settings['track_ad_impressions'] ?? true) ? 'true' : 'false';

        echo '<script>' . "\n";
        echo 'window.poradnikAnalytics = {' . "\n";
        echo '  trackAffiliateClicks: ' . $trackAffiliate . ',' . "\n";
        echo '  trackAdClicks: ' . $trackAdClick . ',' . "\n";
        echo '  trackAdImpressions: ' . $trackAdImpression . "\n";
        echo '};' . "\n";
        echo '</script>' . "\n";
    }

    /**
     * @return array<string, int|float>
     */
    public static function getKpiSummary(): array
    {
        global $wpdb;

        $affiliateClicks = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_affiliate_clicks"
        );

        $adClicks = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_ad_clicks"
        );

        $adImpressions = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_ad_impressions"
        );

        $ctr = $adImpressions > 0 ? round(($adClicks / $adImpressions) * 100, 2) : 0.0;

        $activeCampaigns = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}poradnik_ad_campaigns WHERE status = %s",
                'active'
            )
        );

        $totalRevenue = (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COALESCE(SUM(amount), 0) FROM {$wpdb->prefix}poradnik_sponsored_articles WHERE payment_status = %s",
                'paid'
            )
        );

        return [
            'affiliate_clicks' => $affiliateClicks,
            'ad_clicks' => $adClicks,
            'ad_impressions' => $adImpressions,
            'ad_ctr_percent' => $ctr,
            'active_campaigns' => $activeCampaigns,
            'total_revenue_pln' => $totalRevenue,
        ];
    }
}
