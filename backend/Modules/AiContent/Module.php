<?php

namespace Poradnik\Platform\Modules\AiContent;

use Poradnik\Platform\Admin\AiContentPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            AiContentPage::init();
        }
    }
}
