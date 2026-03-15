<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\UserDashboard\Module::init();

do_action('poradnik_platform_module_loaded', 'UserDashboard');
