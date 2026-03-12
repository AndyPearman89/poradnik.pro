<?php

namespace Poradnik\Platform\Modules\ProgrammaticSeo;

use Poradnik\Platform\Admin\ProgrammaticSeoPage;
use Poradnik\Platform\Domain\Seo\PublicationScheduler;

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

        PublicationScheduler::init();
    }
}
