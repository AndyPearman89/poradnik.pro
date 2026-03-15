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

    /**
     * Returns true if the current user is an advertiser or has post-editing rights.
     * Used as the access guard for advertiser-facing REST endpoints.
     */
    public static function canAccessAsAdvertiser(): bool
    {
        if (self::canManagePlatform()) {
            return true;
        }

        if (! is_user_logged_in()) {
            return false;
        }

        return current_user_can('reklamodawca')
            || current_user_can('advertiser')
            || current_user_can('edit_posts')
            || current_user_can('publish_posts');
    }

    /**
     * Returns true if the current user holds any platform-specific role or has
     * post-editing rights.  Used as the access guard for multi-role REST endpoints.
     */
    public static function canAccessAsPlatformUser(): bool
    {
        if (self::canManagePlatform()) {
            return true;
        }

        if (! is_user_logged_in()) {
            return false;
        }

        return current_user_can('poradnik_specialist')
            || current_user_can('poradnik_advertiser')
            || current_user_can('poradnik_moderator')
            || current_user_can('edit_posts')
            || current_user_can('publish_posts');
    }
}
