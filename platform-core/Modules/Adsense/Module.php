<?php

namespace Poradnik\Platform\Modules\Adsense;

use Poradnik\Platform\Modules\Adsense\Admin\AdsenseSettings;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            AdsenseSettings::init();
        }

        Hooks::register();
    }
}
