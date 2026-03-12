<?php

namespace PPAM\Admin;

use PPAM\Analytics\Stats;
use PPAM\Core\CampaignManager;

if (!defined('ABSPATH')) {
    exit;
}

class Campaigns
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_init', [self::class, 'handleCsvExport']);
        add_action('admin_init', [self::class, 'handleCampaignAction']);
    }

    public static function handleCampaignAction(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['ppam_campaign_admin_action'], $_POST['ppam_campaign_id'])) {
            return;
        }

        check_admin_referer('ppam_campaign_admin_action_nonce');

        $action = isset($_POST['ppam_campaign_admin_action'])
            ? sanitize_key((string) wp_unslash($_POST['ppam_campaign_admin_action']))
            : '';
        $campaignId = isset($_POST['ppam_campaign_id'])
            ? max(0, (int) wp_unslash($_POST['ppam_campaign_id']))
            : 0;

        $ok = CampaignManager::adminAction($campaignId, $action);

        wp_safe_redirect(add_query_arg([
            'page' => 'ppam-campaigns',
            'ppam_action_done' => $ok ? '1' : '0',
            'ppam_action' => $action,
            'campaign' => $campaignId,
        ], admin_url('admin.php')));
        exit;
    }

    public static function handleCsvExport(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['ppam_export_campaigns_csv'])) {
            return;
        }

        check_admin_referer('ppam_export_campaigns_csv_action');

        $campaigns = get_posts([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => 2000,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $filename = 'ppam-kampanie-' . gmdate('Ymd-His') . '.csv';
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            exit;
        }

        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, [
            __('ID', 'peartree-pro-ads-marketplace'),
            __('Nazwa', 'peartree-pro-ads-marketplace'),
            __('Slot', 'peartree-pro-ads-marketplace'),
            __('Status', 'peartree-pro-ads-marketplace'),
            __('Budzet_PLN', 'peartree-pro-ads-marketplace'),
            __('Impresje', 'peartree-pro-ads-marketplace'),
            __('Klikniecia', 'peartree-pro-ads-marketplace'),
            __('CTR_%', 'peartree-pro-ads-marketplace'),
            __('Start', 'peartree-pro-ads-marketplace'),
            __('Koniec', 'peartree-pro-ads-marketplace'),
            __('Metoda_platnosci', 'peartree-pro-ads-marketplace'),
        ]);

        foreach ($campaigns as $campaign) {
            $id = (int) $campaign->ID;
            $slot = (string) get_post_meta($id, '_ppam_slot', true);
            $status = (string) get_post_meta($id, '_ppam_status', true);
            $budget = (float) get_post_meta($id, '_ppam_budget', true);
            $impressions = (int) get_post_meta($id, '_ppam_impressions', true);
            $clicks = (int) get_post_meta($id, '_ppam_clicks', true);
            $ctr = Stats::getCtr($id);
            $start = (string) get_post_meta($id, '_ppam_start_date', true);
            $end = (string) get_post_meta($id, '_ppam_end_date', true);
            $paymentMethod = (string) get_post_meta($id, '_ppam_payment_method', true);

            fputcsv($output, [
                $id,
                (string) $campaign->post_title,
                $slot,
                $status,
                number_format($budget, 2, '.', ''),
                $impressions,
                $clicks,
                number_format($ctr, 2, '.', ''),
                $start,
                $end,
                $paymentMethod,
            ]);
        }

        fclose($output);
        exit;
    }

    public static function registerPage(): void
    {
        add_submenu_page(
            'ppam-marketplace',
            __('Kampanie', 'peartree-pro-ads-marketplace'),
            __('Kampanie', 'peartree-pro-ads-marketplace'),
            'manage_options',
            'ppam-campaigns',
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        $campaigns = get_posts([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        echo '<div class="wrap"><h1>' . esc_html__('Kampanie reklamowe', 'peartree-pro-ads-marketplace') . '</h1>';
        if (isset($_GET['ppam_action_done'])) {
            $done     = ((string) wp_unslash($_GET['ppam_action_done'])) === '1';
            $action   = isset($_GET['ppam_action']) ? sanitize_text_field((string) wp_unslash($_GET['ppam_action'])) : '';
            $campaign = isset($_GET['campaign']) ? max(0, (int) wp_unslash($_GET['campaign'])) : 0;
            if ($done) {
                /* translators: 1: action name, 2: campaign ID */
                echo '<div class="notice notice-success"><p>' . sprintf(esc_html__('Akcja â€ž%1$sâ€ś wykonana dla kampanii #%2$d.', 'peartree-pro-ads-marketplace'), esc_html($action), $campaign) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Nie udaĹ‚o siÄ™ wykonaÄ‡ akcji. SprawdĹş aktualny status kampanii.', 'peartree-pro-ads-marketplace') . '</p></div>';
            }
        }
        echo '<form method="post" action="" style="margin:12px 0 16px 0">';
        wp_nonce_field('ppam_export_campaigns_csv_action');
        submit_button(__('Eksport CSV kampanii', 'peartree-pro-ads-marketplace'), 'secondary', 'ppam_export_campaigns_csv', false);
        echo '</form>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('ID', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('Nazwa', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('Slot', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('Status', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('Impresje', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('KlikniÄ™cia', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('CTR', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('Akcje', 'peartree-pro-ads-marketplace') . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($campaigns as $campaign) {
            $id = (int) $campaign->ID;
            $slot = (string) get_post_meta($id, '_ppam_slot', true);
            $status = (string) get_post_meta($id, '_ppam_status', true);
            $impressions = (int) get_post_meta($id, '_ppam_impressions', true);
            $clicks = (int) get_post_meta($id, '_ppam_clicks', true);
            $ctr = Stats::getCtr($id);
            echo '<tr>';
            echo '<td>' . esc_html((string) $id) . '</td>';
            echo '<td>' . esc_html((string) $campaign->post_title) . '</td>';
            echo '<td>' . esc_html($slot) . '</td>';
            echo '<td>' . esc_html(CampaignManager::getStatusLabel($status)) . '</td>';
            echo '<td>' . esc_html((string) $impressions) . '</td>';
            echo '<td>' . esc_html((string) $clicks) . '</td>';
            echo '<td>' . esc_html(number_format($ctr, 2, ',', ' ') . '%') . '</td>';
            echo '<td>';
            echo '<form method="post" action="" style="display:flex;gap:6px;flex-wrap:wrap">';
            wp_nonce_field('ppam_campaign_admin_action_nonce');
            echo '<input type="hidden" name="ppam_campaign_id" value="' . esc_attr((string) $id) . '">';
            if ($status === 'pending_approval') {
                echo '<button type="submit" class="button button-small button-primary" name="ppam_campaign_admin_action" value="approve">' . esc_html__('Akceptuj', 'peartree-pro-ads-marketplace') . '</button>';
                echo '<button type="submit" class="button button-small" name="ppam_campaign_admin_action" value="reject">' . esc_html__('OdrzuÄ‡', 'peartree-pro-ads-marketplace') . '</button>';
            }
            if ($status === 'active') {
                echo '<button type="submit" class="button button-small" name="ppam_campaign_admin_action" value="pause">' . esc_html__('Wstrzymaj', 'peartree-pro-ads-marketplace') . '</button>';
                echo '<button type="submit" class="button button-small" name="ppam_campaign_admin_action" value="complete">' . esc_html__('ZakoĹ„cz', 'peartree-pro-ads-marketplace') . '</button>';
            }
            if ($status === 'paused') {
                echo '<button type="submit" class="button button-small button-primary" name="ppam_campaign_admin_action" value="resume">' . esc_html__('WznĂłw', 'peartree-pro-ads-marketplace') . '</button>';
                echo '<button type="submit" class="button button-small" name="ppam_campaign_admin_action" value="complete">' . esc_html__('ZakoĹ„cz', 'peartree-pro-ads-marketplace') . '</button>';
            }
            if ($status === 'pending_payment') {
                echo '<button type="submit" class="button button-small" name="ppam_campaign_admin_action" value="reject">' . esc_html__('OdrzuÄ‡', 'peartree-pro-ads-marketplace') . '</button>';
            }
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';
    }
}

