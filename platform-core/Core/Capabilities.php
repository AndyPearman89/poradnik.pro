<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class Capabilities
{
    /** Custom capability granted to the poradnik_platform_admin role. */
    public const CAP_MANAGE = 'manage_poradnik_platform';

    public static function manageCapability(): string
    {
        $capability = apply_filters('poradnik_platform_manage_capability', self::CAP_MANAGE);

        return is_string($capability) && $capability !== '' ? $capability : self::CAP_MANAGE;
    }

    public static function canManagePlatform(): bool
    {
        return current_user_can(self::manageCapability());
    }
}
