<?php

namespace Poradnik\Platform\Domain\Ads;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class CampaignRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function findAll(): array
    {
        global $wpdb;

        $campaignsTable = Migrator::tableName('ad_campaigns');
        $slotsTable = Migrator::tableName('ad_slots');

        $results = $wpdb->get_results(
            "SELECT c.*, s.slot_key, s.label AS slot_label
             FROM {$campaignsTable} c
             LEFT JOIN {$slotsTable} s ON s.id = c.slot_id
             ORDER BY c.id DESC",
            ARRAY_A
        );

        return is_array($results) ? $results : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findById(int $campaignId): ?array
    {
        global $wpdb;

        if ($campaignId < 1) {
            return null;
        }

        $table = Migrator::tableName('ad_campaigns');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $campaignId);
        $result = $wpdb->get_row($query, ARRAY_A);

        return is_array($result) ? $result : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findActiveBySlotKey(string $slotKey): ?array
    {
        global $wpdb;

        $slot = SlotRepository::findByKey($slotKey);
        if (! is_array($slot) || ! isset($slot['id'])) {
            return null;
        }

        $slotId = absint($slot['id']);
        if ($slotId < 1) {
            return null;
        }

        $table = Migrator::tableName('ad_campaigns');
        $now = current_time('mysql', true);

        $query = $wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE slot_id = %d
               AND status = 'active'
               AND (start_date IS NULL OR start_date = '0000-00-00 00:00:00' OR start_date <= %s)
               AND (end_date IS NULL OR end_date = '0000-00-00 00:00:00' OR end_date >= %s)
             ORDER BY id DESC
             LIMIT 1",
            $slotId,
            $now,
            $now
        );

        $result = $wpdb->get_row($query, ARRAY_A);

        return is_array($result) ? $result : null;
    }

    public static function save(array $data, int $campaignId = 0): int
    {
        global $wpdb;

        $table = Migrator::tableName('ad_campaigns');
        $now = current_time('mysql', true);

        $payload = [
            'name' => sanitize_text_field((string) ($data['name'] ?? '')),
            'advertiser_id' => absint($data['advertiser_id'] ?? 0),
            'slot_id' => absint($data['slot_id'] ?? 0),
            'status' => sanitize_key((string) ($data['status'] ?? 'draft')),
            'start_date' => self::normalizeDate($data['start_date'] ?? ''),
            'end_date' => self::normalizeDate($data['end_date'] ?? ''),
            'budget' => is_numeric($data['budget'] ?? null) ? (float) $data['budget'] : 0,
            'destination_url' => esc_url_raw((string) ($data['destination_url'] ?? '')),
            'creative_text' => sanitize_text_field((string) ($data['creative_text'] ?? '')),
            'updated_at' => $now,
        ];

        if ($campaignId > 0) {
            $updated = $wpdb->update(
                $table,
                $payload,
                ['id' => $campaignId],
                ['%s', '%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s'],
                ['%d']
            );

            if ($updated === false) {
                return 0;
            }

            return $campaignId;
        }

        $payload['created_at'] = $now;

        $inserted = $wpdb->insert(
            $table,
            $payload,
            ['%s', '%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s']
        );

        if ($inserted !== 1) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public static function delete(int $campaignId): bool
    {
        global $wpdb;

        if ($campaignId < 1) {
            return false;
        }

        $table = Migrator::tableName('ad_campaigns');
        $deleted = $wpdb->delete($table, ['id' => $campaignId], ['%d']);

        return $deleted === 1;
    }

    private static function normalizeDate($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}
