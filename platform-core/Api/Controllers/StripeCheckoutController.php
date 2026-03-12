<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Domain\Stripe\CheckoutService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class StripeCheckoutController
{
    public static function registerRoutes(): void
    {
        register_rest_route(
            'poradnik/v1',
            '/stripe/checkout',
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'handle'],
                'permission_callback' => [self::class, 'permissionCheck'],
            ]
        );
    }

    public static function permissionCheck(): bool
    {
        return is_user_logged_in();
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function handle(WP_REST_Request $request)
    {
        $orderType   = sanitize_key((string) ($request->get_param('order_type') ?? ''));
        $orderId     = absint($request->get_param('order_id'));
        $amountCents = absint($request->get_param('amount_cents'));
        $currency    = sanitize_text_field((string) ($request->get_param('currency') ?? 'pln'));
        $productName = sanitize_text_field((string) ($request->get_param('product_name') ?? 'Poradnik.Pro Order'));
        $successUrl  = esc_url_raw((string) ($request->get_param('success_url') ?? home_url('/stripe/success')));
        $cancelUrl   = esc_url_raw((string) ($request->get_param('cancel_url') ?? home_url('/stripe/cancel')));

        if (! in_array($orderType, ['sponsored', 'campaign'], true)) {
            return new WP_Error(
                'poradnik_stripe_invalid_type',
                'Parameter order_type must be "sponsored" or "campaign".',
                ['status' => 400]
            );
        }

        if ($orderId < 1 || $amountCents < 50) {
            return new WP_Error(
                'poradnik_stripe_invalid_params',
                'Parameters order_id and amount_cents are required.',
                ['status' => 400]
            );
        }

        $idempotencyKey = 'pp_' . $orderType . '_' . $orderId . '_' . get_current_user_id();

        $session = CheckoutService::createSession([
            'amount_cents'    => $amountCents,
            'currency'        => $currency,
            'product_name'    => $productName,
            'success_url'     => $successUrl,
            'cancel_url'      => $cancelUrl,
            'order_type'      => $orderType,
            'order_id'        => $orderId,
            'idempotency_key' => $idempotencyKey,
        ]);

        if (is_wp_error($session)) {
            return $session;
        }

        $checkoutUrl = (string) ($session['url'] ?? '');

        if ($checkoutUrl === '') {
            return new WP_Error(
                'poradnik_stripe_no_url',
                'Stripe did not return a checkout URL.',
                ['status' => 502]
            );
        }

        return new WP_REST_Response(
            [
                'checkout_url' => $checkoutUrl,
                'session_id'   => (string) ($session['id'] ?? ''),
            ],
            200
        );
    }
}
