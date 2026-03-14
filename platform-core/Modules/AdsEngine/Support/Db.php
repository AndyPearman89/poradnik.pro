<?php

namespace Poradnik\Platform\Modules\AdsEngine\Support;

if (! defined('ABSPATH')) {
    exit;
}

final class Db
{
    public static function table(string $name): string
    {
        global $wpdb;
        return $wpdb->prefix . $name;
    }
}
