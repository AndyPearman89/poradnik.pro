<?php

namespace Poradnik\Platform\Core;

use Poradnik\Platform\Api\RestKernel;
use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class Runtime
{
    private static bool $booted = false;

    public static function init(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        Migrator::init();
        RestKernel::init();
        add_action('init', [self::class, 'onWordPressInit'], 1);

        EventLogger::dispatch('poradnik_platform_runtime_initialized');
    }

    public static function onWordPressInit(): void
    {
        EventLogger::dispatch('poradnik_platform_wordpress_init');
    }
}
