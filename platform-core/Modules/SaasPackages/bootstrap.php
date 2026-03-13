<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\SaasPackages\Module::init();

do_action('poradnik_platform_module_loaded', 'SaasPackages');
