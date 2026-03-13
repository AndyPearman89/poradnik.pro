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

    public static function specialistCapability(): string
    {
        $capability = apply_filters('poradnik_platform_specialist_capability', 'poradnik_specialist');

        return is_string($capability) && $capability !== '' ? $capability : 'poradnik_specialist';
    }

    public static function canAccessSpecialistDashboard(): bool
    {
        return current_user_can(self::specialistCapability()) || self::canManagePlatform();
    }

    public static function moderatorCapability(): string
    {
        $capability = apply_filters('poradnik_platform_moderator_capability', 'poradnik_moderator');

        return is_string($capability) && $capability !== '' ? $capability : 'poradnik_moderator';
    }

    public static function canAccessModeratorDashboard(): bool
    {
        return current_user_can(self::moderatorCapability()) || self::canManagePlatform();
    }

    public static function advertiserCapability(): string
    {
        $capability = apply_filters('poradnik_platform_advertiser_capability', 'poradnik_advertiser');

        return is_string($capability) && $capability !== '' ? $capability : 'poradnik_advertiser';
    }

    public static function canAccessAdvertiserDashboard(): bool
    {
        return current_user_can(self::advertiserCapability()) || self::canManagePlatform();
    }

    public static function userCapability(): string
    {
        return 'read';
    }

    public static function canAccessUserDashboard(): bool
    {
        return is_user_logged_in();
    }
}
