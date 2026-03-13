<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\SecurityHardening\Module::init();

do_action('poradnik_platform_module_loaded', 'SecurityHardening');
