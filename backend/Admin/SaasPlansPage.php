<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\SaasPlans\PlanRepository;

if (! defined('ABSPATH')) {
    exit;
}

final class SaasPlansPage
{
    private const PAGE_SLUG = 'poradnik-saas-plans';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_init', [self::class, 'handleForm']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('SaaS Plans', 'poradnik-platform'),
            __('SaaS Plans', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function handleForm(): void
    {
        if (! isset($_POST['poradnik_saas_plan_nonce'])) {
            return;
        }

        if (! wp_verify_nonce(
            sanitize_text_field(wp_unslash((string) $_POST['poradnik_saas_plan_nonce'])),
            'poradnik_saas_set_plan'
        )) {
            return;
        }

        if (! Capabilities::canManagePlatform()) {
            return;
        }

        $userId = absint($_POST['user_id'] ?? 0);
        $planKey = sanitize_key((string) ($_POST['plan_key'] ?? ''));

        if ($userId > 0 && $planKey !== '') {
            PlanRepository::setUserPlan($userId, $planKey);
        }
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $plans = PlanRepository::all();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('SaaS Plans', 'poradnik-platform') . '</h1>';

        echo '<h2>' . esc_html__('Available Plans', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:900px;">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Plan', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Price', 'poradnik-platform') . '</th>';
        echo '<th>' . esc_html__('Features', 'poradnik-platform') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($plans as $key => $plan) {
            if (! is_array($plan)) {
                continue;
            }

            $label = (string) ($plan['label'] ?? strtoupper($key));
            $price = (float) ($plan['price'] ?? 0);
            $currency = (string) ($plan['currency'] ?? 'PLN');
            $features = is_array($plan['features'] ?? null) ? $plan['features'] : [];

            $priceDisplay = $price > 0 ? number_format($price, 0, '.', '') . ' ' . $currency . '/mo' : esc_html__('Free', 'poradnik-platform');

            echo '<tr>';
            echo '<td><strong>' . esc_html($label) . '</strong></td>';
            echo '<td>' . esc_html($priceDisplay) . '</td>';
            echo '<td><ul style="margin:0;">';
            foreach ($features as $feature) {
                echo '<li>' . esc_html((string) $feature) . '</li>';
            }
            echo '</ul></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        echo '<h2 style="margin-top:24px;">' . esc_html__('Assign Plan to User', 'poradnik-platform') . '</h2>';
        echo '<form method="post" style="max-width:520px;">';
        wp_nonce_field('poradnik_saas_set_plan', 'poradnik_saas_plan_nonce');

        echo '<table class="form-table">';
        echo '<tr><th><label for="poradnik-user-id">' . esc_html__('User ID', 'poradnik-platform') . '</label></th>';
        echo '<td><input type="number" id="poradnik-user-id" name="user_id" min="1" class="regular-text" /></td></tr>';
        echo '<tr><th><label for="poradnik-plan-key">' . esc_html__('Plan', 'poradnik-platform') . '</label></th>';
        echo '<td><select id="poradnik-plan-key" name="plan_key">';
        foreach ($plans as $key => $plan) {
            $label = is_array($plan) ? (string) ($plan['label'] ?? strtoupper($key)) : strtoupper($key);
            echo '<option value="' . esc_attr($key) . '">' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';
        echo '</table>';

        submit_button(__('Assign Plan', 'poradnik-platform'));
        echo '</form>';

        echo '</div>';
    }
}
