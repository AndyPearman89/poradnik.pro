<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\SeoAutomation\Module::init();

do_action('poradnik_platform_module_loaded', 'SeoAutomation');
