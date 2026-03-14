<?php

if (! defined('ABSPATH')) {
    exit;
}

\Poradnik\Platform\Modules\AiImageGenerator\AiImageGeneratorBootstrap::init();

do_action('poradnik_platform_module_loaded', 'AiImageGenerator');
