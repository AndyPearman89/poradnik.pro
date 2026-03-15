<?php

if (! defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/functions.php';

\Poradnik\Platform\Modules\AdsEngine\Module::init();

do_action('poradnik_platform_module_loaded', 'AdsEngine');
