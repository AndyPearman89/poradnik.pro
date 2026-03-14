<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\SpecialistDashboard\Module::init();

do_action('poradnik_platform_module_loaded', 'SpecialistDashboard');
