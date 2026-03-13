<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registers and manages custom platform roles and capabilities.
 * Idempotent – safe to call on every bootstrap.
 */
final class RoleManager
{
    /**
     * Map of role slug => display name + capabilities.
     *
     * @var array<string, array{name: string, caps: array<string, bool>}>
     */
    private const ROLES = [
        'poradnik_user' => [
            'name' => 'Poradnik User',
            'caps' => [
                'read'                       => true,
                'access_user_dashboard'      => true,
                'manage_own_profile'         => true,
            ],
        ],
        'poradnik_specialist' => [
            'name' => 'Poradnik Specialist',
            'caps' => [
                'read'                            => true,
                'access_user_dashboard'           => true,
                'manage_own_profile'              => true,
                'manage_own_specialist_profile'   => true,
                'publish_specialist_content'      => true,
                'view_own_earnings'               => true,
            ],
        ],
        'poradnik_advertiser' => [
            'name' => 'Poradnik Advertiser',
            'caps' => [
                'read'                   => true,
                'access_user_dashboard'  => true,
                'manage_own_profile'     => true,
                'manage_campaigns'       => true,
                'view_own_stats'         => true,
            ],
        ],
    ];

    /**
     * Register all platform roles.
     * Called once during Bootstrap::init(); safe to run multiple times.
     */
    public static function registerRoles(): void
    {
        foreach (self::ROLES as $slug => $definition) {
            if (get_role($slug) !== null) {
                continue;
            }

            add_role($slug, $definition['name'], $definition['caps']);
        }

        EventLogger::dispatch('poradnik_platform_roles_registered', ['roles' => array_keys(self::ROLES)]);
    }

    /**
     * Returns the capabilities array for a given role slug.
     *
     * @return array<string, bool>
     */
    public static function getCapabilities(string $role): array
    {
        return self::ROLES[$role]['caps'] ?? [];
    }

    /**
     * Checks whether the current user has a given platform capability.
     */
    public static function currentUserCan(string $capability): bool
    {
        return current_user_can($capability);
    }
}
