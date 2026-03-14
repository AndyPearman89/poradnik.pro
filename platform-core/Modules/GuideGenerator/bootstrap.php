<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\GuideGenerator\Module::init();

do_action('poradnik_platform_module_loaded', 'GuideGenerator');
