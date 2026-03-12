<?php

namespace PPAM\Payments;

use PPAM\Core\CampaignManager;

if (!defined('ABSPATH')) {
    exit;
}

class Stripe
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
            'ppam_pay' => 'stripe',
            'campaign' => $campaignId,
            'ppam_nonce' => wp_create_nonce('ppam_pay_' . $campaignId),
        ], $returnUrl);
    }

    public static function registerWebhookRoute(): void
    {
        register_rest_route('ppam/v1', '/webhook/stripe', [
            'methods' => 'POST',
            'callback' => [self::class, 'handleWebhook'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function handleWebhook(\WP_REST_Request $request): \WP_REST_Response
    {
        $settings = get_option('ppam_webhook_settings', []);
        $signingSecret = (string) ($settings['stripe_signing_secret'] ?? '');
        if ($signingSecret === '') {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Brak stripe_signing_secret'], 500);
        }

        $payload = (string) $request->get_body();
        $signatureHeader = (string) $request->get_header('stripe-signature');

        if (!self::verifySignature($payload, $signatureHeader, $signingSecret)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Nieprawidłowa sygnatura Stripe'], 401);
        }

        $event = json_decode($payload, true);
        if (!is_array($event)) {
            return new \WP_REST_Response(['ok' => false, 'message' => 'Nieprawidłowy JSON'], 400);
        }

        $type = (string) ($event['type'] ?? '');
        $object = (array) ($event['data']['object'] ?? []);
        $campaignId = (int) (($object['metadata']['campaign_id'] ?? 0) ?: ($object['client_reference_id'] ?? 0));

        if ($campaignId > 0 && in_array($type, ['checkout.session.completed', 'payment_intent.succeeded'], true)) {
            CampaignManager::setStatus($campaignId, 'pending_approval');
            update_post_meta($campaignId, '_ppam_payment_method', 'stripe');
            update_post_meta($campaignId, '_ppam_payment_event', sanitize_text_field($type));
            update_post_meta($campaignId, '_ppam_payment_txn', sanitize_text_field((string) ($object['id'] ?? '')));
        }

        return new \WP_REST_Response(['ok' => true], 200);
    }

    private static function verifySignature(string $payload, string $header, string $secret): bool
    {
        if ($payload === '' || $header === '' || $secret === '') {
            return false;
        }

        $pairs = [];
        foreach (explode(',', $header) as $part) {
            $part = trim($part);
            if (strpos($part, '=') === false) {
                continue;
            }
            [$key, $value] = explode('=', $part, 2);
            $pairs[$key] = $value;
        }

        $timestamp = isset($pairs['t']) ? (int) $pairs['t'] : 0;
        $signature = (string) ($pairs['v1'] ?? '');
        if ($timestamp <= 0 || $signature === '') {
            return false;
        }

        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        return hash_equals($expected, $signature);
    }

    public static function handleReturn(): void
    {
        $gateway = isset($_GET['ppam_pay']) ? sanitize_key((string) wp_unslash($_GET['ppam_pay'])) : '';
        if ($gateway !== 'stripe') {
            return;
        }

        $campaignId = isset($_GET['campaign']) ? max(0, (int) wp_unslash($_GET['campaign'])) : 0;
        $nonce = isset($_GET['ppam_nonce']) ? (string) wp_unslash($_GET['ppam_nonce']) : '';

        if ($campaignId > 0 && wp_verify_nonce($nonce, 'ppam_pay_' . $campaignId)) {
            update_post_meta($campaignId, '_ppam_payment_return', current_time('mysql'));
        }
    }
}
