<?php

namespace PPAM\Core;

use PPAM\Admin\AdsInventory;
use PPAM\Admin\Campaigns;
use PPAM\Admin\Orders;
use PPAM\Analytics\Stats;
use PPAM\Frontend\AdSlots;
use PPAM\Frontend\AdvertiserPanel;
use PPAM\Frontend\CampaignForm;
use PPAM\Frontend\SponsoredLanding;
use PPAM\Payments\PayPal;
use PPAM\Payments\Stripe;

if (!defined('ABSPATH')) {
    exit;
}

class Marketplace
{
    public static function init(): void
    {
        add_action('init', [self::class, 'loadTextdomain']);
        add_action('init', [self::class, 'registerPostType']);
        add_action('init', [self::class, 'maybeScheduleCron']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_filter('body_class', [self::class, 'addBodyClasses']);
        add_action('ppam_expire_campaigns_event', [CampaignManager::class, 'expireExpiredCampaigns']);

        AdsInventory::init();
        Campaigns::init();
        Orders::init();

        AdvertiserPanel::init();
        CampaignForm::init();
        AdSlots::init();
        SponsoredLanding::init();

        Stripe::init();
        PayPal::init();
        Stats::init();
        StatsController::init();
    }

    public static function loadTextdomain(): void
    {
        load_plugin_textdomain(
            'peartree-pro-ads-marketplace',
            false,
            dirname(plugin_basename(PPAM_PATH . 'poradnik-ads-marketplace.php')) . '/languages'
        );
    }

    public static function maybeScheduleCron(): void
    {
        if (!wp_next_scheduled('ppam_expire_campaigns_event')) {
            wp_schedule_event(time() + 300, 'hourly', 'ppam_expire_campaigns_event');
        }
    }

    public static function clearCron(): void
    {
        $timestamp = wp_next_scheduled('ppam_expire_campaigns_event');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'ppam_expire_campaigns_event');
        }
    }

    public static function registerPostType(): void
    {
        register_post_type('ppam_campaign', [
            'label'    => __('Kampanie reklamowe', 'peartree-pro-ads-marketplace'),
            'public'   => false,
            'show_ui'  => false,
            'supports' => ['title', 'author'],
        ]);
    }

    public static function enqueueAssets(): void
    {
        wp_enqueue_style('ppam-marketplace', PPAM_URL . 'assets/marketplace.css', [], PPAM_VERSION);
        wp_enqueue_script('ppam-marketplace', PPAM_URL . 'assets/marketplace.js', ['jquery'], PPAM_VERSION, true);
    }

    public static function addBodyClasses(array $classes): array
    {
        if (!self::isPpamContext()) {
            return $classes;
        }

        $classes[] = 'ppam-context';

        $stylesheet = strtolower((string) wp_get_theme()->get_stylesheet());
        $themeClass = 'ppam-theme-' . sanitize_html_class(str_replace('_', '-', $stylesheet));
        if ($themeClass !== 'ppam-theme-') {
            $classes[] = $themeClass;
        }

        return array_values(array_unique($classes));
    }

    private static function isPpamContext(): bool
    {
        if (is_admin()) {
            return false;
        }

        if (isset($_GET['ppam_pay']) || isset($_GET['ppam_click']) || isset($_GET['ppam_tab'])) {
            return true;
        }

        $panelId = (int) get_option('ppam_page_panel_id', 0);
        $sponsoredId = (int) get_option('ppam_page_sponsored_id', 0);
        if (($panelId > 0 && is_page($panelId)) || ($sponsoredId > 0 && is_page($sponsoredId))) {
            return true;
        }

        if (!is_singular()) {
            return false;
        }

        $post = get_post();
        if (!$post instanceof \WP_Post) {
            return false;
        }

        $content = (string) $post->post_content;
        return has_shortcode($content, 'ppam_advertiser_panel')
            || has_shortcode($content, 'ppam_sponsored_landing')
            || has_shortcode($content, 'ppam_campaign_form')
            || has_shortcode($content, 'ppam_ad_slot');
    }
}

