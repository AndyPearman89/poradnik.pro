<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Registers and manages custom user roles for the PearTree/Dashboard.PRO platform.
 *
 * Roles:
 *  - peartree_specialist  (expert content creator)
 *  - peartree_advertiser  (ads marketplace user)
 *  - peartree_moderator   (content moderation)
 *
 * Built-in WordPress roles (administrator, editor, subscriber) are reused
 * for the platform Admin and User personas respectively.
 */
final class RoleManager
{
    private static bool $booted = false;

    public static function init(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;

        add_action('init', [self::class, 'registerRoles'], 5);
    }

    public static function registerRoles(): void
    {
        self::ensureRole(
            'peartree_specialist',
            __('Specialist', 'poradnik-platform'),
            [
                'read' => true,
                'edit_posts' => true,
                'publish_posts' => false,
                'delete_posts' => false,
                'upload_files' => true,
            ]
        );

        self::ensureRole(
            'peartree_advertiser',
            __('Advertiser', 'poradnik-platform'),
            [
                'read' => true,
                'manage_peartree_ads' => true,
            ]
        );

        self::ensureRole(
            'peartree_moderator',
            __('Moderator', 'poradnik-platform'),
            [
                'read' => true,
                'edit_posts' => true,
                'edit_others_posts' => true,
                'edit_published_posts' => true,
                'delete_posts' => true,
                'delete_others_posts' => true,
                'moderate_comments' => true,
            ]
        );

        EventLogger::dispatch('peartree_roles_registered');
    }

    /**
     * @param array<string, bool> $capabilities
     */
    private static function ensureRole(string $role, string $displayName, array $capabilities): void
    {
        if (get_role($role) === null) {
            add_role($role, $displayName, $capabilities);
        }
    }

    public static function isSpecialist(int $userId = 0): bool
    {
        return self::userHasRole($userId, 'peartree_specialist');
    }

    public static function isAdvertiser(int $userId = 0): bool
    {
        return self::userHasRole($userId, 'peartree_advertiser');
    }

    public static function isModerator(int $userId = 0): bool
    {
        return self::userHasRole($userId, 'peartree_moderator');
    }

    private static function userHasRole(int $userId, string $role): bool
    {
        $id = $userId > 0 ? $userId : get_current_user_id();
        $user = get_userdata($id);

        if (! $user instanceof \WP_User) {
            return false;
        }

        return in_array($role, (array) $user->roles, true);
    }
}
