<?php

namespace Poradnik\Platform\Domain\Ads;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class SlotRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function findAll(): array
    {
        global $wpdb;

        $table = Migrator::tableName('ad_slots');
        $results = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A);

        return is_array($results) ? $results : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByKey(string $slotKey): ?array
    {
        global $wpdb;

        $slotKey = sanitize_key($slotKey);
        if ($slotKey === '') {
            return null;
        }

        $table = Migrator::tableName('ad_slots');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE slot_key = %s LIMIT 1", $slotKey);
        $result = $wpdb->get_row($query, ARRAY_A);

        return is_array($result) ? $result : null;
    }

    public static function ensureDefaults(): void
    {
        global $wpdb;

        $defaults = [
            ['slot_key' => 'homepage-hero', 'label' => 'Homepage Hero', 'location' => 'home'],
            ['slot_key' => 'sidebar-banner', 'label' => 'Sidebar Banner', 'location' => 'sidebar'],
            ['slot_key' => 'inline-article', 'label' => 'Inline Article Ad', 'location' => 'article'],
            ['slot_key' => 'footer-banner', 'label' => 'Footer Banner', 'location' => 'footer'],
        ];

        $table = Migrator::tableName('ad_slots');
        $now = current_time('mysql', true);

        foreach ($defaults as $slot) {
            $exists = self::findByKey($slot['slot_key']);
            if (is_array($exists)) {
                continue;
            }

            $wpdb->insert(
                $table,
                [
                    'slot_key' => $slot['slot_key'],
                    'label' => $slot['label'],
                    'location' => $slot['location'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['%s', '%s', '%s', '%s', '%s']
            );
        }
    }
}
