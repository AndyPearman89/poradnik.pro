<?php

namespace Poradnik\Platform\Core;

if (! defined('ABSPATH')) {
    exit;
}

final class Roles
{
    /**
     * @var array<string, array{display_name: string, capabilities: array<string, bool>}>
     */
    private const ROLES = [
        'specialist' => [
            'display_name' => 'Specialist',
            'capabilities' => [
                'read'                   => true,
                'publish_posts'          => true,
                'edit_posts'             => true,
                'delete_posts'           => true,
                'upload_files'           => true,
                'poradnik_specialist'    => true,
            ],
        ],
        'advertiser' => [
            'display_name' => 'Advertiser',
            'capabilities' => [
                'read'                   => true,
                'poradnik_advertiser'    => true,
            ],
        ],
        'moderator' => [
            'display_name' => 'Moderator',
            'capabilities' => [
                'read'                   => true,
                'edit_posts'             => true,
                'edit_others_posts'      => true,
                'delete_posts'           => true,
                'delete_others_posts'    => true,
                'publish_posts'          => true,
                'moderate_comments'      => true,
                'manage_categories'      => true,
                'upload_files'           => true,
                'poradnik_moderator'     => true,
            ],
        ],
    ];

    public static function register(): void
    {
        foreach (self::ROLES as $role => $config) {
            if (get_role($role) === null) {
                add_role($role, $config['display_name'], $config['capabilities']);
            }
        }
    }

    public static function canAccessAdminDashboard(): bool
    {
        return current_user_can('manage_options');
    }

    public static function canAccessSpecialistDashboard(): bool
    {
        return current_user_can('poradnik_specialist') || current_user_can('manage_options');
    }

    public static function canAccessAdvertiserDashboard(): bool
    {
        return current_user_can('poradnik_advertiser')
            || current_user_can('reklamodawca')
            || current_user_can('advertiser')
            || current_user_can('manage_options');
    }

    public static function canAccessModeratorDashboard(): bool
    {
        return current_user_can('poradnik_moderator') || current_user_can('manage_options');
    }

    public static function canAccessUserDashboard(): bool
    {
        return is_user_logged_in();
    }
}
