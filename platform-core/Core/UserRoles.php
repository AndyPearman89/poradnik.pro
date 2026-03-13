<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registers the custom platform admin role and its capabilities.
 *
 * Role: poradnik_platform_admin
 * Capability: manage_poradnik_platform
 *
 * This role is intended for platform managers who need access to all
 * Poradnik Platform admin pages without requiring full WordPress administrator
 * privileges.
 */
final class UserRoles
{
    public const ROLE_SLUG = 'poradnik_platform_admin';
    public const ROLE_LABEL = 'Poradnik Platform Admin';

    /** Capabilities granted to the poradnik_platform_admin role. */
    private const ROLE_CAPS = [
        'read'                      => true,
        Capabilities::CAP_MANAGE    => true,
    ];

    public static function register(): void
    {
        add_action('init', [self::class, 'maybeAddRole'], 1);
    }

    public static function maybeAddRole(): void
    {
        if (get_role(self::ROLE_SLUG) !== null) {
            return;
        }

        add_role(self::ROLE_SLUG, __(self::ROLE_LABEL, 'poradnik-platform'), self::ROLE_CAPS);

        EventLogger::dispatch('poradnik_platform_admin_role_registered', ['role' => self::ROLE_SLUG]);
    }
}
