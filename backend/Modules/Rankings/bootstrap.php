<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\Rankings\Module::init();

do_action('poradnik_platform_module_loaded', 'Rankings');
