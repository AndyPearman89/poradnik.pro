<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\ContentModel\Module::init();

do_action('poradnik_platform_module_loaded', 'ContentModel');
