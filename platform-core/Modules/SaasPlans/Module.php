<?php

namespace Poradnik\Platform\Modules\SaasPlans;

use Poradnik\Platform\Admin\SaasPlansPage;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            SaasPlansPage::init();
        }
    }
}
