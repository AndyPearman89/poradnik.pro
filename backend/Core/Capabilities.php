<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class Capabilities
{
    public static function manageCapability(): string
    {
        $capability = apply_filters('poradnik_platform_manage_capability', 'manage_options');

        return is_string($capability) && $capability !== '' ? $capability : 'manage_options';
    }

    public static function canManagePlatform(): bool
    {
        return current_user_can(self::manageCapability());
    }
}
