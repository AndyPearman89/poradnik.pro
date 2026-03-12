<?php

namespace Poradnik\Platform\Domain\Stripe;

use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Domain\Sponsored\Workflow;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Verifies Stripe webhook signatures and dispatches domain events.
 *
 * Signature verification algorithm (HMAC-SHA256):
 *   payload  = t_value + '.' + raw_body
 *   expected = hmac_sha256(payload, webhook_secret)
 *   Check:   hash_equals(expected, v1_value)
 */
final class WebhookReceiver
{
    /** Maximum age of a webhook event before it is rejected (seconds). */
    private const TOLERANCE_SECONDS = 300;

    /**
     * @return true|WP_Error
     */
    public static function handle(string $rawBody, string $signatureHeader)
    {
        $webhookSecret = (string) get_option('poradnik_stripe_webhook_secret', '');

        if ($webhookSecret === '') {
            return new WP_Error(
                'poradnik_stripe_webhook_not_configured',
                'Stripe webhook secret not configured.',
                ['status' => 500]
            );
        }

        $verified = self::verifySignature($rawBody, $signatureHeader, $webhookSecret);

        if (is_wp_error($verified)) {
            return $verified;
        }

        $event = json_decode($rawBody, true);

        if (! is_array($event)) {
            return new WP_Error(
                'poradnik_stripe_webhook_invalid_json',
                'Webhook payload is not valid JSON.',
                ['status' => 400]
            );
        }

        $eventId   = (string) ($event['id'] ?? '');
        $eventType = (string) ($event['type'] ?? '');
        $eventData = $event['data']['object'] ?? [];

        if (! is_array($eventData)) {
            return true;
        }

        // Idempotency: skip already-processed events.
        if ($eventId !== '' && self::isProcessed($eventId)) {
            EventLogger::dispatch('poradnik_platform_stripe_webhook_duplicate', ['event_id' => $eventId]);
            return true;
        }

        EventLogger::dispatch('poradnik_platform_stripe_webhook_received', [
            'event_id'   => $eventId,
            'event_type' => $eventType,
        ]);

        switch ($eventType) {
            case 'checkout.session.completed':
                self::handleCheckoutSessionCompleted($eventData);
                break;

            case 'payment_intent.succeeded':
                self::handlePaymentIntentSucceeded($eventData);
                break;

            case 'payment_intent.payment_failed':
                self::handlePaymentIntentFailed($eventData);
                break;
        }

        // Allow external modules to react to any Stripe event.
        do_action(
            'poradnik_platform_stripe_event_' . str_replace('.', '_', $eventType),
            $eventData,
            $event
        );

        if ($eventId !== '') {
            self::markProcessed($eventId, $eventType);
        }

        return true;
    }

    /**
     * @return true|WP_Error
     */
    private static function verifySignature(string $rawBody, string $header, string $secret)
    {
        $parts = [];

        foreach (explode(',', $header) as $chunk) {
            $pair = explode('=', $chunk, 2);
            if (count($pair) === 2) {
                $parts[trim($pair[0])] = trim($pair[1]);
            }
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return new WP_Error(
                'poradnik_stripe_sig_missing',
                'Missing t or v1 in Stripe-Signature header.',
                ['status' => 400]
            );
        }

        $timestamp = (int) $parts['t'];
        $age       = abs(time() - $timestamp);

        if ($age > self::TOLERANCE_SECONDS) {
            return new WP_Error(
                'poradnik_stripe_sig_expired',
                'Stripe webhook timestamp is too old.',
                ['status' => 400]
            );
        }

        $payload  = $timestamp . '.' . $rawBody;
        $expected = hash_hmac('sha256', $payload, $secret);

        if (! hash_equals($expected, $parts['v1'])) {
            return new WP_Error(
                'poradnik_stripe_sig_invalid',
                'Stripe webhook signature mismatch.',
                ['status' => 400]
            );
        }

        return true;
    }

    /**
     * @param array<string, mixed> $session
     */
    private static function handleCheckoutSessionCompleted(array $session): void
    {
        $orderType     = (string) ($session['metadata']['order_type'] ?? '');
        $orderId       = absint($session['metadata']['order_id'] ?? 0);
        $paymentIntent = sanitize_text_field((string) ($session['payment_intent'] ?? ''));

        if ($orderType === 'sponsored' && $orderId > 0) {
            Workflow::markPaid($orderId, $paymentIntent);

            EventLogger::dispatch('poradnik_platform_sponsored_stripe_paid', [
                'order_id'       => $orderId,
                'payment_intent' => $paymentIntent,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $intent
     */
    private static function handlePaymentIntentSucceeded(array $intent): void
    {
        $orderType = (string) ($intent['metadata']['order_type'] ?? '');
        $orderId   = absint($intent['metadata']['order_id'] ?? 0);

        EventLogger::dispatch('poradnik_platform_stripe_payment_intent_succeeded', [
            'order_type'     => $orderType,
            'order_id'       => $orderId,
            'payment_intent' => sanitize_text_field((string) ($intent['id'] ?? '')),
        ]);
    }

    /**
     * @param array<string, mixed> $intent
     */
    private static function handlePaymentIntentFailed(array $intent): void
    {
        $orderType = (string) ($intent['metadata']['order_type'] ?? '');
        $orderId   = absint($intent['metadata']['order_id'] ?? 0);
        $reason    = wp_strip_all_tags((string) ($intent['last_payment_error']['message'] ?? 'Unknown failure'));

        EventLogger::dispatch('poradnik_platform_stripe_payment_intent_failed', [
            'order_type' => $orderType,
            'order_id'   => $orderId,
            'reason'     => $reason,
        ]);
    }

    private static function isProcessed(string $eventId): bool
    {
        global $wpdb;

        $table = $wpdb->prefix . 'poradnik_stripe_sessions';
        $count = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$table} WHERE stripe_event_id = %s",
                $eventId
            )
        );

        return $count > 0;
    }

    private static function markProcessed(string $eventId, string $eventType): void
    {
        global $wpdb;

        $now   = current_time('mysql', true);
        $table = $wpdb->prefix . 'poradnik_stripe_sessions';

        $wpdb->insert(
            $table,
            [
                'stripe_event_id' => $eventId,
                'event_type'      => $eventType,
                'processed_at'    => $now,
                'created_at'      => $now,
                'updated_at'      => $now,
            ],
            ['%s', '%s', '%s', '%s', '%s']
        );
    }
}
