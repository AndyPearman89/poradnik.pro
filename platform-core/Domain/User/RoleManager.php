<?php

namespace Poradnik\Platform\Domain\User;

if (! defined('ABSPATH')) {
    exit;
}

final class RoleManager
{
    public static function registerRoles(): void
    {
        self::maybeAddRole(
            'poradnik_specialist',
            __('Specialist', 'poradnik-platform'),
            [
                'read'                 => true,
                'edit_posts'           => true,
                'publish_posts'        => true,
                'upload_files'         => true,
                'poradnik_specialist'  => true,
            ]
        );

        self::maybeAddRole(
            'poradnik_advertiser',
            __('Advertiser', 'poradnik-platform'),
            [
                'read'                 => true,
                'poradnik_advertiser'  => true,
            ]
        );

        self::maybeAddRole(
            'poradnik_moderator',
            __('Moderator', 'poradnik-platform'),
            [
                'read'                    => true,
                'edit_posts'              => true,
                'edit_others_posts'       => true,
                'delete_posts'            => true,
                'delete_others_posts'     => true,
                'moderate_comments'       => true,
                'poradnik_moderator'      => true,
            ]
        );
    }

    /**
     * @param array<string, bool> $capabilities
     */
    private static function maybeAddRole(string $roleKey, string $displayName, array $capabilities): void
    {
        if (get_role($roleKey) === null) {
            add_role($roleKey, $displayName, $capabilities);
        }
    }
}
