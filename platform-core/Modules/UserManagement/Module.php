<?php

namespace Poradnik\Platform\Modules\UserManagement;

use Poradnik\Platform\Admin\UserDashboardPage;
use Poradnik\Platform\Domain\User\RoleManager;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_action('init', [self::class, 'registerRoles'], 1);

        if (is_admin()) {
            UserDashboardPage::init();
        }
    }

    public static function registerRoles(): void
    {
        RoleManager::registerRoles();
    }
}
