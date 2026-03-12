<?php
/**
 * Plugin Name: peartree.pro Ads Marketplace
 * Description: Marketplace reklam dla peartree.pro - kampanie, sloty, statystyki i pĹ‚atnoĹ›ci.
 * Version: 1.0.0
 * Author: peartree.pro
 * Text Domain: peartree-pro-ads-marketplace
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PPAM_VERSION', '1.0.0');
define('PPAM_PATH', plugin_dir_path(__FILE__));
define('PPAM_URL', plugin_dir_url(__FILE__));

add_action('plugins_loaded', static function (): void {
    load_plugin_textdomain('peartree-pro-ads-marketplace', false, dirname(plugin_basename(__FILE__)) . '/languages');
}, 5);

require_once PPAM_PATH . 'core/Marketplace.php';
require_once PPAM_PATH . 'core/EmailNotifier.php';
require_once PPAM_PATH . 'core/CampaignManager.php';
require_once PPAM_PATH . 'core/StatsController.php';
require_once PPAM_PATH . 'admin/AdsInventory.php';
require_once PPAM_PATH . 'admin/Campaigns.php';
require_once PPAM_PATH . 'admin/Orders.php';
require_once PPAM_PATH . 'frontend/CampaignForm.php';
require_once PPAM_PATH . 'frontend/AdvertiserPanel.php';
require_once PPAM_PATH . 'frontend/AdSlots.php';
require_once PPAM_PATH . 'frontend/SponsoredLanding.php';
require_once PPAM_PATH . 'payments/Stripe.php';
require_once PPAM_PATH . 'payments/PayPal.php';
require_once PPAM_PATH . 'analytics/Stats.php';

function ppam_create_page_if_missing(string $title, string $slug, string $content): int
{
    $existing = get_page_by_path($slug, OBJECT, 'page');
    if ($existing instanceof WP_Post) {
        return (int) $existing->ID;
    }

    $pageId = wp_insert_post([
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_name' => $slug,
        'post_content' => $content,
    ]);

    return is_wp_error($pageId) ? 0 : (int) $pageId;
}

function ppam_assign_theme_template(int $pageId, string $template): void
{
    if ($pageId <= 0 || get_post_type($pageId) !== 'page') {
        return;
    }

    $templatePath = get_theme_file_path($template);
    if (!is_string($templatePath) || $templatePath === '' || !file_exists($templatePath)) {
        return;
    }

    update_post_meta($pageId, '_wp_page_template', $template);
}

function ppam_activate_plugin(): void
{
    $panelId = ppam_create_page_if_missing('Panel reklamodawcy', 'panel-reklamodawcy', '[ppam_advertiser_panel]');
    $sponsoredId = ppam_create_page_if_missing('Oferty sponsorowane', 'oferty-sponsorowane', '[ppam_sponsored_landing]');

    ppam_assign_theme_template($sponsoredId, 'template-marketplace-ads.php');
    ppam_assign_theme_template($panelId, 'template-marketplace-panel.php');

    update_option('ppam_page_panel_id', $panelId, false);
    update_option('ppam_page_sponsored_id', $sponsoredId, false);

    \PPAM\Core\Marketplace::maybeScheduleCron();
}
register_activation_hook(__FILE__, 'ppam_activate_plugin');

function ppam_deactivate_plugin(): void
{
    \PPAM\Core\Marketplace::clearCron();
}
register_deactivation_hook(__FILE__, 'ppam_deactivate_plugin');

function ppam_maybe_migrate_template_assignment(): void
{
    if (get_option('ppam_template_assignment_done_v2', '0') === '1') {
        return;
    }

    $panelId = (int) get_option('ppam_page_panel_id', 0);
    if ($panelId <= 0) {
        $existingPanel = get_page_by_path('panel-reklamodawcy', OBJECT, 'page');
        if ($existingPanel instanceof WP_Post) {
            $panelId = (int) $existingPanel->ID;
            update_option('ppam_page_panel_id', $panelId, false);
        }
    }

    $sponsoredId = (int) get_option('ppam_page_sponsored_id', 0);
    if ($sponsoredId <= 0) {
        $existing = get_page_by_path('oferty-sponsorowane', OBJECT, 'page');
        if ($existing instanceof WP_Post) {
            $sponsoredId = (int) $existing->ID;
            update_option('ppam_page_sponsored_id', $sponsoredId, false);
        }
    }

    ppam_assign_theme_template($sponsoredId, 'template-marketplace-ads.php');
    ppam_assign_theme_template($panelId, 'template-marketplace-panel.php');
    update_option('ppam_template_assignment_done_v2', '1', false);
}
add_action('admin_init', 'ppam_maybe_migrate_template_assignment');

add_action('plugins_loaded', static function () {
    \PPAM\Core\Marketplace::init();
});

