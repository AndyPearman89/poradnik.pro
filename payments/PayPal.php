<?php

namespace PPAM\Payments;

use PPAM\Core\CampaignManager;

if (!defined('ABSPATH')) {
    exit;
}

class PayPal
{
    public static function init(): void
    {
        add_action('template_redirect', [self::class, 'handleReturn']);
        add_action('rest_api_init', [self::class, 'registerWebhookRoute']);
    }

    public static function getCheckoutUrl(int $campaignId, string $returnUrl = ''): string
    {
        $returnUrl = $returnUrl !== '' ? esc_url_raw($returnUrl) : home_url('/panel-reklamodawcy/');

        return add_query_arg([
            'ppam_pay' => 'paypal',
            'campaign' => $campaignId,
            'ppam_nonce' => wp_create_nonce('ppam_pay_' . $campaignId),
        ], $returnUrl);
    }

    public static function registerWebhookRoute(): void
    {
        register_rest_route('ppam/v1', '/webhook/paypal', [
            'methods' => 'POST',
            'callback' => [self::class, 'handleWebhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handleWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $settings = get_option('ppam_webhook_settings', []);
        $token = (string) ($settings['paypal_webhook_token'] ?? '');
        if ($token === '') {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Brak paypal_webhook_token'], 500);
        }

        $receivedToken = (string) $request->get_header('x-ppam-paypal-token');
        if (!hash_equals($token, $receivedToken)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Nieprawidłowy token webhooka PayPal'], 401);
        }

        $event = json_decode((string) $request->get_body(), true);
        if (!is_array($event)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Nieprawidłowy JSON'], 400);
        }

        $eventType = (string) ($event['event_type'] ?? '');
        $resource = (array) ($event['resource'] ?? []);
        $campaignId = (int) ($resource['custom_id'] ?? 0);

        if ($campaignId > 0 && in_array($eventType, ['PAYMENT.CAPTURE.COMPLETED', 'CHECKOUT.ORDER.APPROVED', 'CHECKOUT.ORDER.COMPLETED'], true)) {
            CampaignManager::setStatus($campaignId, 'pending_approval');
            update_post_meta($campaignId, '_ppam_payment_method', 'paypal');
            update_post_meta($campaignId, '_ppam_payment_event', sanitize_text_field($eventType));
            update_post_meta($campaignId, '_ppam_payment_txn', sanitize_text_field((string) ($resource['id'] ?? '')));
        }

        return new \WP_REST_Response(['ok' => true], 200);
    }

    public static function handleReturn(): void
    {
        $gateway = isset($_GET['ppam_pay']) ? sanitize_key((string) wp_unslash($_GET['ppam_pay'])) : '';
        if ($gateway !== 'paypal') {
            return;
        }

        $campaignId = isset($_GET['campaign']) ? max(0, (int) wp_unslash($_GET['campaign'])) : 0;
        $nonce = isset($_GET['ppam_nonce']) ? (string) wp_unslash($_GET['ppam_nonce']) : '';

        if ($campaignId > 0 && wp_verify_nonce($nonce, 'ppam_pay_' . $campaignId)) {
            update_post_meta($campaignId, '_ppam_payment_return', current_time('mysql'));
        }
    }
}
