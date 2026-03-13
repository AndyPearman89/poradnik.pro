<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\Setup\Module::init();

do_action('poradnik_platform_module_loaded', 'Setup');
