<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\SaasPlans\Module::init();

do_action('poradnik_platform_module_loaded', 'SaasPlans');
