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

    public static function canAccessSpecialistDashboard(): bool
    {
        return current_user_can('edit_posts') || self::canManagePlatform();
    }

    public static function canAccessAdvertiserDashboard(): bool
    {
        return current_user_can('manage_peartree_ads') || self::canManagePlatform();
    }

    public static function canAccessModeratorDashboard(): bool
    {
        return current_user_can('moderate_comments') || self::canManagePlatform();
    }

    public static function canAccessUserDashboard(): bool
    {
        return is_user_logged_in();
    }
}
