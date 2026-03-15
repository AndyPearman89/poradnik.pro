<?php

namespace Poradnik\Platform\Domain\Stripe;

use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

final class CheckoutService
{
    private const API_URL = 'https://api.stripe.com/v1/checkout/sessions';

    /**
     * Creates a Stripe Checkout Session and returns the session data.
     *
     * @param array<string, mixed> $params {
     *     @type int    $amount_cents   Amount in smallest currency unit (e.g. 4990 for 49.90 PLN).
     *     @type string $currency       ISO 4217 lowercase (default: pln).
     *     @type string $product_name   Line item label shown at Stripe checkout.
     *     @type string $success_url    URL after successful payment. {CHECKOUT_SESSION_ID} will be appended.
     *     @type string $cancel_url     URL after cancelled payment.
     *     @type string $order_type     'sponsored' or 'campaign'.
     *     @type int    $order_id       Internal order/campaign ID stored in Stripe metadata.
     *     @type string $idempotency_key Optional Idempotency-Key for safe retries.
     * }
     * @return array<string, mixed>|WP_Error
     */
    public static function createSession(array $params)
    {
        $secretKey = (string) get_option('poradnik_stripe_secret_key', '');

        if ($secretKey === '') {
            return new WP_Error(
                'poradnik_stripe_not_configured',
                'Stripe secret key not configured. Add it under Settings → Stripe.',
                ['status' => 500]
            );
        }

        $amountCents = absint($params['amount_cents'] ?? 0);
        $currency     = strtolower(sanitize_text_field((string) ($params['currency'] ?? 'pln')));
        $productName  = sanitize_text_field((string) ($params['product_name'] ?? 'Poradnik.Pro Order'));
        $successUrl   = esc_url_raw((string) ($params['success_url'] ?? home_url('/stripe/success')));
        $cancelUrl    = esc_url_raw((string) ($params['cancel_url'] ?? home_url('/stripe/cancel')));
        $orderType    = sanitize_key((string) ($params['order_type'] ?? 'sponsored'));
        $orderId      = absint($params['order_id'] ?? 0);
        $idempotencyKey = sanitize_text_field((string) ($params['idempotency_key'] ?? ''));

        if ($amountCents < 50) {
            return new WP_Error(
                'poradnik_stripe_invalid_amount',
                'Amount must be at least 50 (smallest currency unit).',
                ['status' => 400]
            );
        }

        $body = [
            'line_items[0][price_data][currency]'             => $currency,
            'line_items[0][price_data][unit_amount]'          => (string) $amountCents,
            'line_items[0][price_data][product_data][name]'   => $productName,
            'line_items[0][quantity]'                         => '1',
            'mode'                                            => 'payment',
            'success_url'                                     => $successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'                                      => $cancelUrl,
            'metadata[order_type]'                            => $orderType,
            'metadata[order_id]'                              => (string) $orderId,
            'payment_intent_data[metadata][order_type]'       => $orderType,
            'payment_intent_data[metadata][order_id]'         => (string) $orderId,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ];

        if ($idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        $response = wp_remote_post(
            self::API_URL,
            [
                'headers'   => $headers,
                'body'      => $body,
                'timeout'   => 15,
                'sslverify' => true,
            ]
        );

        if (is_wp_error($response)) {
            return new WP_Error(
                'poradnik_stripe_request_failed',
                $response->get_error_message(),
                ['status' => 502]
            );
        }

        $code    = (int) wp_remote_retrieve_response_code($response);
        $rawBody = wp_remote_retrieve_body($response);
        $data    = json_decode($rawBody, true);

        if (! is_array($data)) {
            return new WP_Error(
                'poradnik_stripe_invalid_response',
                'Invalid response from Stripe API.',
                ['status' => 502]
            );
        }

        if ($code !== 200) {
            $message = (string) ($data['error']['message'] ?? 'Stripe returned an error.');
            $httpStatus = ($code >= 400 && $code < 600) ? $code : 502;

            return new WP_Error('poradnik_stripe_api_error', $message, ['status' => $httpStatus]);
        }

        return $data;
    }
}
