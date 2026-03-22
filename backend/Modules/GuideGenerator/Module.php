<?php

namespace Poradnik\Platform\Modules\GuideGenerator;

use Poradnik\Platform\Admin\GuideGeneratorPage;
use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Domain\Guide\GuideGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            GuideGeneratorPage::init();
        }

        add_action('init', [self::class, 'register'], 20);
    }

    public static function register(): void
    {
        EventLogger::dispatch('poradnik_platform_guide_generator_registered', [
            'supported_guide_types' => GuideGenerator::supportedGuideTypes(),
        ]);
    }
}
