<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class EventLogger
{
    public static function dispatch(string $hook, array $context = []): void
    {
        do_action($hook, $context);

        if (! self::isDebugEnabled()) {
            return;
        }

        $payload = [
            'hook' => $hook,
            'context' => $context,
        ];

        error_log('Poradnik Platform: ' . wp_json_encode($payload));
    }

    private static function isDebugEnabled(): bool
    {
        return (bool) apply_filters('poradnik_platform_debug_logging_enabled', false);
    }
}
