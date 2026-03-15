<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\AdsMarketplace\Module::init();

do_action('poradnik_platform_module_loaded', 'AdsMarketplace');
