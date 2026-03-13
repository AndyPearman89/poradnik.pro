<?php

namespace Poradnik\Platform\Modules\SecurityHardening;

use Poradnik\Platform\Admin\SecurityAuditPage;
use Poradnik\Platform\Domain\Security\SecurityAuditLogger;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            SecurityAuditPage::init();
        }

        add_action('wp_login', [self::class, 'onLogin'], 10, 2);
        add_action('wp_login_failed', [self::class, 'onLoginFailed']);
        add_action('user_register', [self::class, 'onUserRegistered']);
        add_action('delete_user', [self::class, 'onUserDeleted']);
        add_action('profile_update', [self::class, 'onProfileUpdated']);
    }

    public static function onLogin(string $userLogin, \WP_User $user): void
    {
        SecurityAuditLogger::log(
            'user_login',
            sprintf('User "%s" (ID: %d) logged in.', $userLogin, $user->ID),
            $user->ID
        );
    }

    public static function onLoginFailed(string $username): void
    {
        SecurityAuditLogger::log(
            'login_failed',
            sprintf('Failed login attempt for username: "%s".', sanitize_user($username)),
            0
        );
    }

    public static function onUserRegistered(int $userId): void
    {
        SecurityAuditLogger::log(
            'user_registered',
            sprintf('New user registered with ID: %d.', $userId),
            $userId
        );
    }

    public static function onUserDeleted(int $userId): void
    {
        SecurityAuditLogger::log(
            'user_deleted',
            sprintf('User with ID %d was deleted.', $userId),
            0
        );
    }

    public static function onProfileUpdated(int $userId): void
    {
        SecurityAuditLogger::log(
            'profile_updated',
            sprintf('User profile updated for ID: %d.', $userId),
            $userId
        );
    }
}
