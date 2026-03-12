<?php

namespace PPAM\Admin;

if (!defined('ABSPATH')) {
    exit;
}

class Orders
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_submenu_page(
            'ppam-marketplace',
            __('ZamĂłwienia', 'peartree-pro-ads-marketplace'),
            __('ZamĂłwienia', 'peartree-pro-ads-marketplace'),
            'manage_options',
            'ppam-orders',
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

        echo '<div class="wrap"><h1>' . esc_html__('ZamĂłwienia reklamowe', 'peartree-pro-ads-marketplace') . '</h1>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>' . esc_html__('Kampania', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('BudĹĽet', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('Metoda pĹ‚atnoĹ›ci', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('Status pĹ‚atnoĹ›ci', 'peartree-pro-ads-marketplace') . '</th>';
        echo '<th>' . esc_html__('Okres', 'peartree-pro-ads-marketplace') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($campaigns as $campaign) {
            $id = (int) $campaign->ID;
            $budget = (float) get_post_meta($id, '_ppam_budget', true);
            $status = (string) get_post_meta($id, '_ppam_status', true);
            $start = (string) get_post_meta($id, '_ppam_start_date', true);
            $end = (string) get_post_meta($id, '_ppam_end_date', true);
            $methodRaw     = (string) get_post_meta($id, '_ppam_payment_method', true);
            $paymentMethod = $methodRaw !== '' ? strtoupper($methodRaw) : __('Do opĹ‚acenia', 'peartree-pro-ads-marketplace');
            $paymentStatus = $status === 'active' ? __('OpĹ‚acone', 'peartree-pro-ads-marketplace') : __('Oczekuje', 'peartree-pro-ads-marketplace');

            echo '<tr>';
            echo '<td>' . esc_html((string) $campaign->post_title) . '</td>';
            echo '<td>' . esc_html(number_format($budget, 2, ',', ' ') . ' PLN') . '</td>';
            echo '<td>' . esc_html($paymentMethod) . '</td>';
            echo '<td>' . esc_html($paymentStatus) . '</td>';
            echo '<td>' . esc_html($start . ' - ' . $end) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table></div>';
    }
}

