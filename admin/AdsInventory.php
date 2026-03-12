<?php

namespace PPAM\Admin;

use PPAM\Core\CampaignManager;
use PPAM\Payments\PayPal;
use PPAM\Payments\Stripe;

if (!defined('ABSPATH')) {
    exit;
}

class AdsInventory
{
    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_init', [self::class, 'handleWebhookSettingsSave']);
        add_action('admin_init', [self::class, 'handleTestWebhook']);
        add_action('admin_init', [self::class, 'handleExpireNow']);
    }

    public static function handleWebhookSettingsSave(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['ppam_save_webhooks'])) {
            return;
        }

        check_admin_referer('ppam_save_webhooks_action');

        $settings = get_option('ppam_webhook_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $settings['stripe_signing_secret'] = isset($_POST['stripe_signing_secret'])
            ? sanitize_text_field((string) wp_unslash($_POST['stripe_signing_secret']))
            : '';
        $settings['paypal_webhook_token'] = isset($_POST['paypal_webhook_token'])
            ? sanitize_text_field((string) wp_unslash($_POST['paypal_webhook_token']))
            : '';

        update_option('ppam_webhook_settings', $settings, false);

        wp_safe_redirect(add_query_arg(['page' => 'ppam-marketplace', 'saved' => '1'], admin_url('admin.php')));
        exit;
    }

    public static function registerPage(): void
    {
        add_menu_page(
            __('Marketplace Reklam', 'peartree-pro-ads-marketplace'),
            __('Marketplace Reklam', 'peartree-pro-ads-marketplace'),
            'manage_options',
            'ppam-marketplace',
            [self::class, 'render'],
            'dashicons-megaphone',
            58
        );
    }

    public static function handleTestWebhook(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['ppam_test_webhook'])) {
            return;
        }

        check_admin_referer('ppam_test_webhook_action');

        $gateway = isset($_POST['ppam_gateway']) ? sanitize_key((string) wp_unslash($_POST['ppam_gateway'])) : '';
        $campaignIdFromSelect = isset($_POST['ppam_campaign_id_select']) ? max(0, (int) wp_unslash($_POST['ppam_campaign_id_select'])) : 0;
        $campaignIdFromInput = isset($_POST['ppam_campaign_id']) ? max(0, (int) wp_unslash($_POST['ppam_campaign_id'])) : 0;
        $campaignId = $campaignIdFromSelect > 0 ? $campaignIdFromSelect : $campaignIdFromInput;
        $result = 'error';

        if ($campaignId > 0 && in_array($gateway, ['stripe', 'paypal'], true)) {
            $settings = get_option('ppam_webhook_settings', []);
            if (!is_array($settings)) {
                $settings = [];
            }

            if ($gateway === 'stripe') {
                $secret = (string) ($settings['stripe_signing_secret'] ?? '');
                if ($secret !== '') {
                    $payload = wp_json_encode([
                        'type' => 'checkout.session.completed',
                        'data' => [
                            'object' => [
                                'id' => 'test_stripe_' . $campaignId,
                                'metadata' => [
                                    'campaign_id' => $campaignId,
                                ],
                            ],
                        ],
                    ]);

                    if (is_string($payload) && $payload !== '') {
                        $timestamp = time();
                        $signature = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
                        $request = new \WP_REST_Request('POST', '/ppam/v1/webhook/stripe');
                        $request->set_body($payload);
                        $request->set_header('stripe-signature', 't=' . $timestamp . ',v1=' . $signature);
                        $response = Stripe::handleWebhook($request);
                        if ((int) $response->get_status() === 200) {
                            $result = 'success';
                        }
                    }
                }
            }

            if ($gateway === 'paypal') {
                $token = (string) ($settings['paypal_webhook_token'] ?? '');
                if ($token !== '') {
                    $payload = wp_json_encode([
                        'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
                        'resource' => [
                            'id' => 'test_paypal_' . $campaignId,
                            'custom_id' => $campaignId,
                        ],
                    ]);

                    if (is_string($payload) && $payload !== '') {
                        $request = new \WP_REST_Request('POST', '/ppam/v1/webhook/paypal');
                        $request->set_body($payload);
                        $request->set_header('x-ppam-paypal-token', $token);
                        $response = PayPal::handleWebhook($request);
                        if ((int) $response->get_status() === 200) {
                            $result = 'success';
                        }
                    }
                }
            }
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'ppam-marketplace',
            'webhook_test' => $result,
            'gateway' => $gateway,
            'campaign' => $campaignId,
        ], admin_url('admin.php')));
        exit;
    }

    public static function handleExpireNow(): void
    {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        if (!isset($_POST['ppam_expire_campaigns_now'])) {
            return;
        }

        check_admin_referer('ppam_expire_campaigns_now_action');

        $expiredCount = CampaignManager::expireExpiredCampaigns();

        wp_safe_redirect(add_query_arg([
            'page' => 'ppam-marketplace',
            'expired_now' => (int) $expiredCount,
        ], admin_url('admin.php')));
        exit;
    }

    public static function render(): void
    {
        $slots = CampaignManager::getSlots();
        $overview = CampaignManager::getOverviewStats();
        $recentCampaigns = CampaignManager::getRecentCampaigns(7);
        $settings = get_option('ppam_webhook_settings', []);
        if (!is_array($settings)) {
            $settings = [];
        }

        $stripeSecret = (string) ($settings['stripe_signing_secret'] ?? '');
        $paypalToken = (string) ($settings['paypal_webhook_token'] ?? '');
        $stripeWebhookUrl = rest_url('ppam/v1/webhook/stripe');
        $paypalWebhookUrl = rest_url('ppam/v1/webhook/paypal');
        $statsEndpointUrl = rest_url('ppam/v1/stats');
        $campaignsEndpointUrl = rest_url('ppam/v1/campaigns');
        $campaigns = get_posts([
            'post_type' => 'ppam_campaign',
            'post_status' => 'publish',
            'posts_per_page' => 200,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        echo '<div class="wrap"><h1>' . esc_html__('Marketplace Reklam â€“ Sloty', 'peartree-pro-ads-marketplace') . '</h1>';
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;max-width:1080px;margin:14px 0 18px 0">';
        echo '<div style="padding:12px;border:1px solid #dcdcde;background:#fff"><strong>' . esc_html__('Kampanie', 'peartree-pro-ads-marketplace') . ':</strong><br>' . esc_html((string) ((int) ($overview['total'] ?? 0))) . '</div>';
        echo '<div style="padding:12px;border:1px solid #dcdcde;background:#fff"><strong>' . esc_html__('Aktywne', 'peartree-pro-ads-marketplace') . ':</strong><br>' . esc_html((string) ((int) ($overview['active'] ?? 0))) . '</div>';
        echo '<div style="padding:12px;border:1px solid #dcdcde;background:#fff"><strong>' . esc_html__('Do akceptacji', 'peartree-pro-ads-marketplace') . ':</strong><br>' . esc_html((string) ((int) ($overview['pending_approval'] ?? 0))) . '</div>';
        echo '<div style="padding:12px;border:1px solid #dcdcde;background:#fff"><strong>' . esc_html__('Budżet łączny', 'peartree-pro-ads-marketplace') . ':</strong><br>' . esc_html(number_format((float) ($overview['total_budget'] ?? 0), 2, ',', ' ') . ' PLN') . '</div>';
        echo '<div style="padding:12px;border:1px solid #dcdcde;background:#fff"><strong>' . esc_html__('CTR globalny', 'peartree-pro-ads-marketplace') . ':</strong><br>' . esc_html(number_format((float) ($overview['avg_ctr'] ?? 0), 2, ',', ' ') . '%') . '</div>';
        echo '</div>';

        echo '<p><strong>' . esc_html__('REST (panel admin):', 'peartree-pro-ads-marketplace') . '</strong> <code>' . esc_html($statsEndpointUrl) . '</code> | <code>' . esc_html($campaignsEndpointUrl) . '</code></p>';

        if (!empty($recentCampaigns)) {
            echo '<h2>' . esc_html__('Ostatnie kampanie', 'peartree-pro-ads-marketplace') . '</h2>';
            echo '<table class="widefat striped" style="max-width:1080px;margin-bottom:16px"><thead><tr><th>ID</th><th>' . esc_html__('Nazwa', 'peartree-pro-ads-marketplace') . '</th><th>' . esc_html__('Status', 'peartree-pro-ads-marketplace') . '</th><th>' . esc_html__('Budżet', 'peartree-pro-ads-marketplace') . '</th><th>CTR</th></tr></thead><tbody>';
            foreach ($recentCampaigns as $row) {
                echo '<tr>';
                echo '<td>' . esc_html((string) ((int) ($row['id'] ?? 0))) . '</td>';
                echo '<td>' . esc_html((string) ($row['title'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($row['status_label'] ?? '')) . '</td>';
                echo '<td>' . esc_html(number_format((float) ($row['budget'] ?? 0), 2, ',', ' ') . ' PLN') . '</td>';
                echo '<td>' . esc_html(number_format((float) ($row['ctr'] ?? 0), 2, ',', ' ') . '%') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        if (isset($_GET['saved']) && (string) wp_unslash($_GET['saved']) === '1') {
            echo '<div class="notice notice-success"><p>' . esc_html__('Ustawienia webhookĂłw zapisane.', 'peartree-pro-ads-marketplace') . '</p></div>';
        }
        if (isset($_GET['expired_now'])) {
            $expiredNow = max(0, (int) wp_unslash($_GET['expired_now']));
            /* translators: %d: number of expired campaigns */
            echo '<div class="notice notice-success"><p>' . sprintf(esc_html__('Wygaszanie wykonane. ZakoĹ„czono kampanii: %d.', 'peartree-pro-ads-marketplace'), $expiredNow) . '</p></div>';
        }
        if (isset($_GET['webhook_test'])) {
            $gateway  = isset($_GET['gateway']) ? sanitize_text_field((string) wp_unslash($_GET['gateway'])) : '';
            $campaign = isset($_GET['campaign']) ? max(0, (int) wp_unslash($_GET['campaign'])) : 0;
            if ((string) wp_unslash($_GET['webhook_test']) === 'success') {
                /* translators: 1: gateway name uppercase, 2: campaign ID */
                echo '<div class="notice notice-success"><p>' . sprintf(esc_html__('Webhook testowy (%1$s) zakoĹ„czony sukcesem dla kampanii #%2$d.', 'peartree-pro-ads-marketplace'), esc_html(strtoupper($gateway)), $campaign) . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Webhook testowy nie powiĂłdĹ‚ siÄ™. SprawdĹş token/secret i ID kampanii.', 'peartree-pro-ads-marketplace') . '</p></div>';
            }
        }
        echo '<table class="widefat striped" style="max-width:900px"><thead><tr><th>' . esc_html__('Slot', 'peartree-pro-ads-marketplace') . '</th><th>' . esc_html__('Nazwa', 'peartree-pro-ads-marketplace') . '</th><th>' . esc_html__('Cena bazowa (PLN)', 'peartree-pro-ads-marketplace') . '</th></tr></thead><tbody>';
        foreach ($slots as $key => $slot) {
            echo '<tr><td>' . esc_html($key) . '</td><td>' . esc_html((string) $slot['label']) . '</td><td>' . esc_html((string) $slot['price']) . '</td></tr>';
        }
        echo '</tbody></table>';
        echo '<p>' . esc_html__('Sloty sÄ… wykorzystywane przez formularz kampanii reklamodawcy.', 'peartree-pro-ads-marketplace') . '</p></div>';

        echo '<div class="wrap" style="margin-top:20px"><h2>' . esc_html__('Webhooki pĹ‚atnoĹ›ci', 'peartree-pro-ads-marketplace') . '</h2>';
        echo '<p>' . esc_html__('Skonfiguruj endpointy po stronie operatorĂłw pĹ‚atnoĹ›ci i podaj sekrety.', 'peartree-pro-ads-marketplace') . '</p>';
        echo '<table class="form-table" role="presentation" style="max-width:980px"><tbody>';
        echo '<tr><th scope="row">' . esc_html__('Stripe webhook URL', 'peartree-pro-ads-marketplace') . '</th><td><code>' . esc_html($stripeWebhookUrl) . '</code></td></tr>';
        /* translators: header name in code tag */
        echo '<tr><th scope="row">' . esc_html__('PayPal webhook URL', 'peartree-pro-ads-marketplace') . '</th><td><code>' . esc_html($paypalWebhookUrl) . '</code><br><small>' . sprintf(esc_html__('Wymagany nagĹ‚Ăłwek: %s', 'peartree-pro-ads-marketplace'), '<code>X-PPAM-PayPal-Token</code>') . '</small></td></tr>';
        echo '</tbody></table>';

        echo '<form method="post" action="">';
        wp_nonce_field('ppam_save_webhooks_action');
        echo '<table class="form-table" role="presentation" style="max-width:980px"><tbody>';
        echo '<tr><th scope="row"><label for="stripe_signing_secret">' . esc_html__('Stripe signing secret', 'peartree-pro-ads-marketplace') . '</label></th><td><input id="stripe_signing_secret" type="text" class="regular-text" name="stripe_signing_secret" value="' . esc_attr($stripeSecret) . '"></td></tr>';
        echo '<tr><th scope="row"><label for="paypal_webhook_token">' . esc_html__('PayPal webhook token', 'peartree-pro-ads-marketplace') . '</label></th><td><input id="paypal_webhook_token" type="text" class="regular-text" name="paypal_webhook_token" value="' . esc_attr($paypalToken) . '"></td></tr>';
        echo '</tbody></table>';
        submit_button(__('Zapisz webhooki', 'peartree-pro-ads-marketplace'), 'primary', 'ppam_save_webhooks');
        echo '</form></div>';

        echo '<div class="wrap" style="margin-top:20px"><h2>' . esc_html__('Test webhookĂłw', 'peartree-pro-ads-marketplace') . '</h2>';
        echo '<p>' . esc_html__('WyĹ›lij testowy webhook i automatycznie aktywuj wybranÄ… kampaniÄ™.', 'peartree-pro-ads-marketplace') . '</p>';
        echo '<form method="post" action="">';
        wp_nonce_field('ppam_test_webhook_action');
        echo '<table class="form-table" role="presentation" style="max-width:980px"><tbody>';
        echo '<tr><th scope="row"><label for="ppam_campaign_id_select">' . esc_html__('Kampania (lista)', 'peartree-pro-ads-marketplace') . '</label></th><td><select id="ppam_campaign_id_select" name="ppam_campaign_id_select"><option value="0">' . esc_html__('â€” wybierz kampaniÄ™ â€”', 'peartree-pro-ads-marketplace') . '</option>';
        foreach ($campaigns as $campaign) {
            $campaignId = (int) $campaign->ID;
            $status     = (string) get_post_meta($campaignId, '_ppam_status', true);
            echo '<option value="' . esc_attr((string) $campaignId) . '">#' . esc_html((string) $campaignId) . ' â€” ' . esc_html((string) $campaign->post_title) . ' (' . esc_html($status) . ')</option>';
        }
        echo '</select><p class="description">' . esc_html__('WybĂłr z listy ma priorytet nad rÄ™cznym polem ID.', 'peartree-pro-ads-marketplace') . '</p></td></tr>';
        echo '<tr id="ppam_campaign_id_row"><th scope="row"><label for="ppam_campaign_id">' . esc_html__('ID kampanii', 'peartree-pro-ads-marketplace') . '</label></th><td><input id="ppam_campaign_id" type="number" min="1" class="small-text" name="ppam_campaign_id"></td></tr>';
        echo '<tr><th scope="row">' . esc_html__('Operator', 'peartree-pro-ads-marketplace') . '</th><td><label><input type="radio" name="ppam_gateway" value="stripe" checked> Stripe</label> &nbsp; <label><input type="radio" name="ppam_gateway" value="paypal"> PayPal</label></td></tr>';
        echo '</tbody></table>';
        submit_button(__('WyĹ›lij webhook testowy', 'peartree-pro-ads-marketplace'), 'secondary', 'ppam_test_webhook');
        echo '</form>';
        echo '<script>(function(){var select=document.getElementById("ppam_campaign_id_select");var input=document.getElementById("ppam_campaign_id");var row=document.getElementById("ppam_campaign_id_row");if(!select||!input||!row){return;}var sync=function(){var hasSelect=select.value&&select.value!=="0";input.disabled=hasSelect;input.required=!hasSelect;row.style.opacity=hasSelect?"0.55":"1";if(hasSelect){input.value="";}};select.addEventListener("change",sync);sync();})();</script>';
        echo '</div>';

        echo '<div class="wrap" style="margin-top:20px"><h2>' . esc_html__('Wygaszanie kampanii', 'peartree-pro-ads-marketplace') . '</h2>';
        echo '<p>' . esc_html__('RÄ™czne uruchomienie procesu wygaszania kampanii, ktĂłrych data koĹ„cowa juĹĽ minÄ™Ĺ‚a.', 'peartree-pro-ads-marketplace') . '</p>';
        $toExpireCount = CampaignManager::countExpiredActiveCampaigns();
        /* translators: %d: number of campaigns to expire */
        echo '<p><strong>' . esc_html__('Do wygaszenia teraz:', 'peartree-pro-ads-marketplace') . '</strong> ' . esc_html((string) $toExpireCount) . '</p>';
        echo '<form method="post" action="">';
        wp_nonce_field('ppam_expire_campaigns_now_action');
        submit_button(__('Uruchom wygaszanie teraz', 'peartree-pro-ads-marketplace'), 'secondary', 'ppam_expire_campaigns_now');
        echo '</form></div>';
    }
}

