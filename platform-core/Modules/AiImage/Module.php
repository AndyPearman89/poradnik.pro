<?php

namespace Poradnik\Platform\Modules\AiImage;

use Poradnik\Platform\Admin\AiImagePage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            AiImagePage::init();
        }
    }
}
