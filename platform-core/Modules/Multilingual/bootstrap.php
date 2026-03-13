<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\Multilingual\Module::init();

do_action('poradnik_platform_module_loaded', 'Multilingual');
