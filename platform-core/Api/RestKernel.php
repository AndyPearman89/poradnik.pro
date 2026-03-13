<?php

namespace Poradnik\Platform\Api;

use Poradnik\Platform\Api\Controllers\AdClickController;
use Poradnik\Platform\Api\Controllers\AdImpressionController;
use Poradnik\Platform\Api\Controllers\AiContentController;
use Poradnik\Platform\Api\Controllers\AiImageController;
use Poradnik\Platform\Api\Controllers\AffiliateClickController;
use Poradnik\Platform\Api\Controllers\DashboardController;
use Poradnik\Platform\Api\Controllers\HealthController;
use Poradnik\Platform\Api\Controllers\PearTreeDashboardController;
use Poradnik\Platform\Api\Controllers\ProgrammaticBuildController;
use Poradnik\Platform\Api\Controllers\RankingController;
use Poradnik\Platform\Api\Controllers\ReviewController;
use Poradnik\Platform\Api\Controllers\SponsoredOrderController;
use Poradnik\Platform\Api\Controllers\StripeCheckoutController;
use Poradnik\Platform\Api\Controllers\StripeWebhookController;
use Poradnik\Platform\Core\EventLogger;

if (! defined('ABSPATH')) {
    exit;
}

final class RestKernel
{
    private static bool $booted = false;

    public static function init(): void
    {
        if (self::$booted) {
            return;
        }

        self::$booted = true;
        add_action('rest_api_init', [self::class, 'registerRoutes']);
    }

    public static function registerRoutes(): void
    {
        HealthController::registerRoutes();
        AffiliateClickController::registerRoutes();
        AdImpressionController::registerRoutes();
        AdClickController::registerRoutes();
        SponsoredOrderController::registerRoutes();
        DashboardController::registerRoutes();
        PearTreeDashboardController::registerRoutes();
        AiContentController::registerRoutes();
        AiImageController::registerRoutes();
        ProgrammaticBuildController::registerRoutes();
        RankingController::registerRoutes();
        ReviewController::registerRoutes();
        StripeWebhookController::registerRoutes();
        StripeCheckoutController::registerRoutes();

        EventLogger::dispatch('poradnik_platform_rest_routes_registered');
    }
}
