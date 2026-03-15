<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\ModeratorDashboard\Module::init();

do_action('poradnik_platform_module_loaded', 'ModeratorDashboard');
