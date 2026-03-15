<?php

namespace Poradnik\Platform\Modules\ModeratorDashboard;

use Poradnik\Platform\Admin\ModeratorDashboardPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            ModeratorDashboardPage::init();
        }
    }
}
