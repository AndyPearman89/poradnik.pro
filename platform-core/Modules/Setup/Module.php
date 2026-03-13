<?php

namespace Poradnik\Platform\Modules\Setup;

use Poradnik\Platform\Domain\Setup\DataSeeder;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_action('init', [DataSeeder::class, 'maybeSeed'], 30);
    }
}
