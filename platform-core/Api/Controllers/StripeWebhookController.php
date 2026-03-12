<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Domain\Stripe\WebhookReceiver;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

final class StripeWebhookController
{
    public static function registerRoutes(): void
    {
        register_rest_route(
            'poradnik/v1',
            '/stripe/webhook',
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'handle'],
                'permission_callback' => '__return_true',
            ]
        );
    }

    /**
     * @return WP_REST_Response|WP_Error
     */
    public static function handle(WP_REST_Request $request)
    {
        $rawBody         = $request->get_body();
        $signatureHeader = (string) ($request->get_header('stripe_signature') ?? '');

        if ($signatureHeader === '') {
            return new WP_Error(
                'poradnik_stripe_sig_missing',
                'Missing Stripe-Signature header.',
                ['status' => 400]
            );
        }

        if ($rawBody === '') {
            return new WP_Error(
                'poradnik_stripe_empty_body',
                'Empty request body.',
                ['status' => 400]
            );
        }

        $result = WebhookReceiver::handle($rawBody, $signatureHeader);

        if (is_wp_error($result)) {
            return $result;
        }

        return new WP_REST_Response(['received' => true], 200);
    }
}
