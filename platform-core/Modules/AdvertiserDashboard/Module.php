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
        if (is_admin()) {
            AdvertiserDashboardPage::init();
        }
    }
}
