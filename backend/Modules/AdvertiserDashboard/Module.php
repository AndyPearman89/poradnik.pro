<?php

namespace Poradnik\Platform\Modules\AdvertiserDashboard;

use Poradnik\Platform\Admin\AdvertiserDashboardPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_action('init', [self::class, 'registerRoles'], 5);

        if (is_admin()) {
            AdvertiserDashboardPage::init();
        }
    }

    public static function registerRoles(): void
    {
        if (get_role('reklamodawca') !== null) {
            return;
        }

        add_role(
            'reklamodawca',
            'Reklamodawca',
            [
                'read' => true,
                'upload_files' => true,
            ]
        );
    }
}
