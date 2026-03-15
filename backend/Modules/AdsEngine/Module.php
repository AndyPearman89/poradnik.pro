<?php

namespace Poradnik\Platform\Modules\AdsEngine;

use Poradnik\Platform\Modules\AdsEngine\Database\Schema;
use Poradnik\Platform\Modules\AdsEngine\Repository\Campaigns;
use Poradnik\Platform\Modules\AdsEngine\Rest\Routes;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_action('init', [Schema::class, 'maybeMigrate'], 3);
        add_action('init', [self::class, 'registerRewrite'], 11);
        add_filter('query_vars', [self::class, 'registerQueryVars']);
        add_action('template_redirect', [self::class, 'handleAdClick']);
        add_action('rest_api_init', [Routes::class, 'register']);
    }

    public static function registerRewrite(): void
    {
        add_rewrite_tag('%poradnik_ads_click%', '([0-9]+)');
        add_rewrite_rule('^ad-click/([0-9]+)/?$', 'index.php?poradnik_ads_click=$matches[1]', 'top');

        $versionKey = 'poradnik_platform_ads_engine_rewrite_version';
        $target = '1';
        if ((string) get_option($versionKey, '') !== $target) {
            flush_rewrite_rules(false);
            update_option($versionKey, $target, false);
        }
    }

    public static function registerQueryVars(array $vars): array
    {
        $vars[] = 'poradnik_ads_click';
        return $vars;
    }

    public static function handleAdClick(): void
    {
        $adId = absint(get_query_var('poradnik_ads_click'));
        if ($adId < 1) {
            return;
        }

        $ad = Campaigns::findAdById($adId);
        if (! is_array($ad)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            exit;
        }

        Campaigns::incrementClicks($adId);

        $target = esc_url_raw((string) ($ad['target_url'] ?? ''));
        if ($target === '') {
            $target = home_url('/');
        }

        wp_safe_redirect($target, 302, 'PoradnikPlatformAdsEngine');
        exit;
    }
}
