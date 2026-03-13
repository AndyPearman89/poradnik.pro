<?php

namespace Poradnik\Platform\Modules\SpecialistDashboard;

use Poradnik\Platform\Admin\SpecialistDashboardPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            SpecialistDashboardPage::init();
        }
    }
}
