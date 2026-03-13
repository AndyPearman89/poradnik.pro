<?php

namespace Poradnik\Platform\Modules\AdsEngine\Repository;

use Poradnik\Platform\Modules\AdsEngine\Support\Db;

if (! defined('ABSPATH')) {
    exit;
}

final class Campaigns
{
    public static function forUser(int $userId): array
    {
        global $wpdb;
        $table = Db::table('advertiser_campaigns');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d ORDER BY id DESC", $userId);
        $rows = $wpdb->get_results($query, ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public static function create(array $payload): int
    {
        global $wpdb;

        $table = Db::table('advertiser_campaigns');
        $adsTable = Db::table('ads');
        $now = current_time('mysql', true);

        $row = [
            'user_id' => absint($payload['user_id'] ?? 0),
            'campaign_name' => sanitize_text_field((string) ($payload['campaign_name'] ?? '')),
            'campaign_type' => sanitize_text_field((string) ($payload['campaign_type'] ?? 'banner')),
            'status' => sanitize_key((string) ($payload['status'] ?? 'draft')),
            'start_date' => self::normalizeDate($payload['start_date'] ?? ''),
            'end_date' => self::normalizeDate($payload['end_date'] ?? ''),
            'budget' => is_numeric($payload['budget'] ?? null) ? (float) $payload['budget'] : 0,
            'slot_id' => absint($payload['slot_id'] ?? 0),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $ok = $wpdb->insert($table, $row, ['%d', '%s', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%s']);
        if ($ok !== 1) {
            return 0;
        }

        $campaignId = (int) $wpdb->insert_id;

        $wpdb->insert(
            $adsTable,
            [
                'campaign_id' => $campaignId,
                'slot_id' => absint($payload['slot_id'] ?? 0),
                'creative_url' => esc_url_raw((string) ($payload['creative_url'] ?? '')),
                'target_url' => esc_url_raw((string) ($payload['target_url'] ?? '')),
                'clicks' => 0,
                'impressions' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            ['%d', '%d', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        return $campaignId;
    }

    public static function analyticsForUser(int $userId): array
    {
        global $wpdb;

        $campaignsTable = Db::table('advertiser_campaigns');
        $adsTable = Db::table('ads');

        $query = $wpdb->prepare(
            "SELECT c.id, c.campaign_name, c.status, c.budget, a.impressions, a.clicks
             FROM {$campaignsTable} c
             LEFT JOIN {$adsTable} a ON a.campaign_id = c.id
             WHERE c.user_id = %d
             ORDER BY c.id DESC",
            $userId
        );

        $rows = $wpdb->get_results($query, ARRAY_A);
        $rows = is_array($rows) ? $rows : [];

        $impressions = 0;
        $clicks = 0;
        $budget = 0.0;

        foreach ($rows as $row) {
            $impressions += (int) ($row['impressions'] ?? 0);
            $clicks += (int) ($row['clicks'] ?? 0);
            $budget += (float) ($row['budget'] ?? 0);
        }

        $ctr = $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0.0;

        return [
            'campaigns' => $rows,
            'totals' => [
                'active_campaigns' => count(array_filter($rows, static fn($r) => (string) ($r['status'] ?? '') === 'active')),
                'impressions' => $impressions,
                'clicks' => $clicks,
                'ctr' => $ctr,
                'budget' => $budget,
            ],
        ];
    }

    public static function findAdById(int $adId): ?array
    {
        global $wpdb;
        $table = Db::table('ads');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $adId);
        $row = $wpdb->get_row($query, ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public static function findActiveAdBySlot(string $slotName): ?array
    {
        global $wpdb;
        $slot = Slots::findByName($slotName);
        if (! is_array($slot)) {
            return null;
        }

        $ads = Db::table('ads');
        $campaigns = Db::table('advertiser_campaigns');
        $slotId = absint($slot['id'] ?? 0);
        if ($slotId < 1) {
            return null;
        }

        $now = current_time('mysql', true);
        $query = $wpdb->prepare(
            "SELECT a.*
             FROM {$ads} a
             INNER JOIN {$campaigns} c ON c.id = a.campaign_id
             WHERE a.slot_id = %d
               AND c.status = 'active'
               AND (c.start_date IS NULL OR c.start_date <= %s)
               AND (c.end_date IS NULL OR c.end_date >= %s)
             ORDER BY c.id DESC
             LIMIT 1",
            $slotId,
            $now,
            $now
        );

        $row = $wpdb->get_row($query, ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public static function incrementImpressions(int $adId): void
    {
        global $wpdb;
        $table = Db::table('ads');
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET impressions = impressions + 1, updated_at = %s WHERE id = %d", current_time('mysql', true), $adId));
    }

    public static function incrementClicks(int $adId): void
    {
        global $wpdb;
        $table = Db::table('ads');
        $wpdb->query($wpdb->prepare("UPDATE {$table} SET clicks = clicks + 1, updated_at = %s WHERE id = %d", current_time('mysql', true), $adId));
    }

    private static function normalizeDate($raw): ?string
    {
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $ts = strtotime($raw);
        return $ts ? gmdate('Y-m-d H:i:s', $ts) : null;
    }
}
