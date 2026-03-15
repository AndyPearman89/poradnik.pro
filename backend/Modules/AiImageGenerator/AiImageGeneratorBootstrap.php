<?php

namespace Poradnik\Platform\Modules\AiImageGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class AiImageGeneratorBootstrap
{
    public static function init(): void
    {
        AiImageGeneratorService::init();
        ImageQueueWorker::init();

        if (is_admin()) {
            AdminImageGeneratorPage::init();
        }
    }
}
