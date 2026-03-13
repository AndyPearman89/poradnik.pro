<?php

namespace Poradnik\Platform\Modules\AdsEngine\Repository;

use Poradnik\Platform\Modules\AdsEngine\Support\Db;

if (! defined('ABSPATH')) {
    exit;
}

final class Slots
{
    public static function all(): array
    {
        global $wpdb;
        $table = Db::table('ad_slots');
        $rows = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public static function findByName(string $slotName): ?array
    {
        global $wpdb;
        $table = Db::table('ad_slots');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE slot_name = %s LIMIT 1", sanitize_text_field($slotName));
        $row = $wpdb->get_row($query, ARRAY_A);
        return is_array($row) ? $row : null;
    }
}
