<?php

namespace Poradnik\Platform\Modules\ProgrammaticSeo;

use Poradnik\Platform\Admin\ProgrammaticSeoPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            ProgrammaticSeoPage::init();
        }
    }
}
