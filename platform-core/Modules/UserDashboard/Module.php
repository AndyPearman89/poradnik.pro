<?php

namespace Poradnik\Platform\Modules\UserDashboard;

use Poradnik\Platform\Admin\UserDashboardPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            UserDashboardPage::init();
        }
    }
}
