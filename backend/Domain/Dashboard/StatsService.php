<?php

namespace Poradnik\Platform\Domain\Dashboard;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class StatsService
{
    /**
     * @return array<string, mixed>
     */
    public static function overview(int $advertiserId = 0): array
    {
        $campaigns = self::campaigns($advertiserId);
        $statistics = self::statistics($advertiserId);
        $payments = self::payments($advertiserId);

        return [
            'campaigns_total' => count($campaigns),
            'campaigns_active' => count(array_filter($campaigns, static fn (array $row): bool => (string) ($row['status'] ?? '') === 'active')),
            'impressions' => $statistics['impressions'],
            'clicks' => $statistics['clicks'],
            'ctr' => $statistics['ctr'],
            'payments_total' => $payments['total'],
            'payments_paid' => $payments['paid_total'],
            'currency' => $payments['currency'],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function campaigns(int $advertiserId = 0): array
    {
        global $wpdb;

        $campaignsTable = Migrator::tableName('ad_campaigns');
        $slotsTable = Migrator::tableName('ad_slots');
        $clicksTable = Migrator::tableName('ad_clicks');
        $impressionsTable = Migrator::tableName('ad_impressions');

        $where = '';
        $params = [];

        if ($advertiserId > 0) {
            $where = 'WHERE c.advertiser_id = %d';
            $params[] = $advertiserId;
        }

        $sql = "SELECT
                    c.id,
                    c.name,
                    c.status,
                    c.budget,
                    c.start_date,
                    c.end_date,
                    c.destination_url,
                    s.slot_key,
                    COALESCE(imp.total_impressions, 0) AS impressions,
                    COALESCE(clk.total_clicks, 0) AS clicks
                FROM {$campaignsTable} c
                LEFT JOIN {$slotsTable} s ON s.id = c.slot_id
                LEFT JOIN (
                    SELECT campaign_id, COUNT(*) AS total_impressions
                    FROM {$impressionsTable}
                    GROUP BY campaign_id
                ) imp ON imp.campaign_id = c.id
                LEFT JOIN (
                    SELECT campaign_id, COUNT(*) AS total_clicks
                    FROM {$clicksTable}
                    GROUP BY campaign_id
                ) clk ON clk.campaign_id = c.id
                {$where}
                ORDER BY c.id DESC";

        if ($params !== []) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $results = $wpdb->get_results($sql, ARRAY_A);

        return is_array($results) ? $results : [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function statistics(int $advertiserId = 0): array
    {
        global $wpdb;

        $campaignsTable = Migrator::tableName('ad_campaigns');
        $clicksTable = Migrator::tableName('ad_clicks');
        $impressionsTable = Migrator::tableName('ad_impressions');

        $impressions = 0;
        $clicks = 0;

        if ($advertiserId > 0) {
            $impressions = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                     FROM {$impressionsTable} i
                     INNER JOIN {$campaignsTable} c ON c.id = i.campaign_id
                     WHERE c.advertiser_id = %d",
                    $advertiserId
                )
            );

            $clicks = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*)
                     FROM {$clicksTable} i
                     INNER JOIN {$campaignsTable} c ON c.id = i.campaign_id
                     WHERE c.advertiser_id = %d",
                    $advertiserId
                )
            );
        } else {
            $impressions = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$impressionsTable}");
            $clicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$clicksTable}");
        }

        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0;

        return [
            'impressions' => $impressions,
            'clicks' => $clicks,
            'ctr' => $ctr,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function payments(int $advertiserId = 0): array
    {
        global $wpdb;

        $sponsoredTable = Migrator::tableName('sponsored_articles');

        $where = '';
        $params = [];

        if ($advertiserId > 0) {
            $where = 'WHERE advertiser_id = %d';
            $params[] = $advertiserId;
        }

        $sql = "SELECT
                    COUNT(*) AS total,
                    COALESCE(SUM(amount), 0) AS total_amount,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN amount ELSE 0 END), 0) AS paid_total,
                    MAX(currency) AS currency
                FROM {$sponsoredTable}
                {$where}";

        if ($params !== []) {
            $sql = $wpdb->prepare($sql, $params);
        }

        $row = $wpdb->get_row($sql, ARRAY_A);
        if (! is_array($row)) {
            return ['total' => 0, 'total_amount' => 0.0, 'paid_total' => 0.0, 'currency' => 'PLN'];
        }

        return [
            'total' => (int) ($row['total'] ?? 0),
            'total_amount' => (float) ($row['total_amount'] ?? 0),
            'paid_total' => (float) ($row['paid_total'] ?? 0),
            'currency' => (string) ($row['currency'] ?: 'PLN'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function paymentItems(int $advertiserId = 0, int $limit = 20): array
    {
        global $wpdb;

        $sponsoredTable = Migrator::tableName('sponsored_articles');
        $limit = max(1, min(100, $limit));

        $where = '';
        $params = [];

        if ($advertiserId > 0) {
            $where = 'WHERE advertiser_id = %d';
            $params[] = $advertiserId;
        }

        $sql = "SELECT id, title, amount, currency, status, payment_status, package_key, created_at
                FROM {$sponsoredTable}
                {$where}
                ORDER BY id DESC
                LIMIT %d";

        $params[] = $limit;
        $query = $wpdb->prepare($sql, $params);
        $rows = $wpdb->get_results($query, ARRAY_A);

        if (! is_array($rows)) {
            return [];
        }

        $items = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $items[] = [
                'id' => absint($row['id'] ?? 0),
                'date' => (string) ($row['created_at'] ?? ''),
                'campaign' => (string) ($row['title'] ?? 'Kampania sponsorowana'),
                'amount' => (float) ($row['amount'] ?? 0),
                'currency' => (string) ($row['currency'] ?? 'PLN'),
                'status' => (string) ($row['payment_status'] ?? $row['status'] ?? 'pending'),
                'payment_method' => ((string) ($row['package_key'] ?? '') === 'stripe' || ! empty($row['payment_status'])) ? 'stripe' : 'przelewy24',
            ];
        }

        return $items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function series(int $advertiserId = 0, string $period = 'daily'): array
    {
        global $wpdb;

        $campaignsTable = Migrator::tableName('ad_campaigns');
        $clicksTable = Migrator::tableName('ad_clicks');
        $impressionsTable = Migrator::tableName('ad_impressions');

        $period = sanitize_key($period);
        $groupExpr = "DATE_FORMAT(created_at, '%Y-%m-%d')";
        $limit = 30;

        if ($period === 'weekly') {
            $groupExpr = "DATE_FORMAT(created_at, '%x-W%v')";
            $limit = 12;
        } elseif ($period === 'monthly') {
            $groupExpr = "DATE_FORMAT(created_at, '%Y-%m')";
            $limit = 12;
        }

        $impressions = self::seriesByTable($impressionsTable, $campaignsTable, $advertiserId, $groupExpr, $limit);
        $clicks = self::seriesByTable($clicksTable, $campaignsTable, $advertiserId, $groupExpr, $limit);

        $labels = array_values(array_unique(array_merge(array_keys($impressions), array_keys($clicks))));
        sort($labels);

        $output = [];
        foreach ($labels as $label) {
            $views = (int) ($impressions[$label] ?? 0);
            $clickCount = (int) ($clicks[$label] ?? 0);
            $ctr = $views > 0 ? round(($clickCount / $views) * 100, 2) : 0.0;

            $output[] = [
                'label' => $label,
                'views' => $views,
                'clicks' => $clickCount,
                'ctr' => $ctr,
            ];
        }

        return $output;
    }

    /**
     * @return array<string, int>
     */
    private static function seriesByTable(string $eventsTable, string $campaignsTable, int $advertiserId, string $groupExpr, int $limit): array
    {
        global $wpdb;

        if ($advertiserId > 0) {
            $sql = $wpdb->prepare(
                "SELECT {$groupExpr} AS period_label, COUNT(*) AS total
                 FROM {$eventsTable} e
                 INNER JOIN {$campaignsTable} c ON c.id = e.campaign_id
                 WHERE c.advertiser_id = %d
                 GROUP BY period_label
                 ORDER BY period_label DESC
                 LIMIT %d",
                $advertiserId,
                $limit
            );
        } else {
            $sql = $wpdb->prepare(
                "SELECT {$groupExpr} AS period_label, COUNT(*) AS total
                 FROM {$eventsTable}
                 GROUP BY period_label
                 ORDER BY period_label DESC
                 LIMIT %d",
                $limit
            );
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        if (! is_array($rows)) {
            return [];
        }

        $result = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $label = (string) ($row['period_label'] ?? '');
            if ($label === '') {
                continue;
            }

            $result[$label] = (int) ($row['total'] ?? 0);
        }

        return $result;
    }
}
