<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\ProgrammaticSeo\Module::init();

do_action('poradnik_platform_module_loaded', 'ProgrammaticSeo');
