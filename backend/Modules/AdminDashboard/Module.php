<?php

namespace Poradnik\Platform\Modules\AdminDashboard;

use Poradnik\Platform\Admin\AdminDashboardPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            AdminDashboardPage::init();
        }
    }
}
