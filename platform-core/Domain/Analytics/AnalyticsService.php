<?php

namespace Poradnik\Platform\Domain\Analytics;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class AnalyticsService
{
    /**
     * @return array<string, mixed>
     */
    public static function traffic(): array
    {
        $cached = get_transient('poradnik_analytics_traffic');
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;

        $clicksTable = Migrator::tableName('ad_clicks');
        $impressionsTable = Migrator::tableName('ad_impressions');

        $totalImpressions = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$impressionsTable}");
        $totalClicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$clicksTable}");
        $ctr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 2) : 0.0;

        $result = [
            'impressions' => $totalImpressions,
            'clicks'      => $totalClicks,
            'ctr'         => $ctr,
        ];

        set_transient('poradnik_analytics_traffic', $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function revenue(): array
    {
        $cached = get_transient('poradnik_analytics_revenue');
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;

        $sponsoredTable = Migrator::tableName('sponsored_articles');
        $campaignsTable = Migrator::tableName('ad_campaigns');

        $sponsoredRevenue = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(amount), 0) FROM {$sponsoredTable} WHERE payment_status = 'paid'"
        );

        $activeCampaigns = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$campaignsTable} WHERE status = 'active'"
        );

        $totalCampaigns = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$campaignsTable}");

        $result = [
            'sponsored_revenue' => $sponsoredRevenue,
            'active_campaigns'  => $activeCampaigns,
            'total_campaigns'   => $totalCampaigns,
            'currency'          => 'PLN',
        ];

        set_transient('poradnik_analytics_revenue', $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public static function conversion(): array
    {
        $cached = get_transient('poradnik_analytics_conversion');
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;

        $affiliateClicks = Migrator::tableName('affiliate_clicks');
        $affiliateProducts = Migrator::tableName('affiliate_products');

        $totalAffiliateClicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$affiliateClicks}");
        $totalProducts = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$affiliateProducts}");

        $result = [
            'affiliate_clicks'   => $totalAffiliateClicks,
            'affiliate_products' => $totalProducts,
        ];

        set_transient('poradnik_analytics_conversion', $result, 5 * MINUTE_IN_SECONDS);

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function topPages(): array
    {
        global $wpdb;

        $clicksTable = Migrator::tableName('ad_clicks');

        $results = $wpdb->get_results(
            "SELECT source_url, COUNT(*) AS clicks
             FROM {$clicksTable}
             WHERE source_url IS NOT NULL AND source_url <> ''
             GROUP BY source_url
             ORDER BY clicks DESC
             LIMIT 10",
            ARRAY_A
        );

        return is_array($results) ? $results : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function topAffiliates(): array
    {
        global $wpdb;

        $affiliateClicks = Migrator::tableName('affiliate_clicks');
        $affiliateProducts = Migrator::tableName('affiliate_products');

        $results = $wpdb->get_results(
            "SELECT p.name, COUNT(c.id) AS clicks
             FROM {$affiliateClicks} c
             INNER JOIN {$affiliateProducts} p ON p.id = c.product_id
             GROUP BY p.id, p.name
             ORDER BY clicks DESC
             LIMIT 10",
            ARRAY_A
        );

        return is_array($results) ? $results : [];
    }
}
