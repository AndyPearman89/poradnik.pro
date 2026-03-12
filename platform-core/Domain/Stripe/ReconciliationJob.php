<?php

namespace Poradnik\Platform\Domain\Stripe;

use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Domain\Sponsored\OrderRepository;
use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * WP Cron job that reconciles payment status between Stripe and the local DB.
 *
 * Registered as a daily cron event: 'poradnik_stripe_reconcile'
 * For each sponsored order with payment_status='paid' but workflow status!='published',
 * it fires a domain event so other modules can react (e.g. auto-publish or alert).
 */
final class ReconciliationJob
{
    private const CRON_HOOK    = 'poradnik_stripe_reconcile';
    private const CRON_SCHEDULE = 'twicedaily';

    public static function init(): void
    {
        add_action(self::CRON_HOOK, [self::class, 'run']);

        if (! wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    public static function run(): void
    {
        global $wpdb;

        $table = Migrator::tableName('sponsored_articles');

        /** @var array<int, array<string, mixed>>|null $rows */
        $rows = $wpdb->get_results(
            "SELECT id, status, payment_status, stripe_payment_intent
             FROM {$table}
             WHERE payment_status = 'paid'
               AND status NOT IN ('published', 'cancelled')
             ORDER BY id ASC
             LIMIT 50",
            ARRAY_A
        );

        if (! is_array($rows) || $rows === []) {
            return;
        }

        foreach ($rows as $row) {
            $orderId       = (int) $row['id'];
            $status        = (string) $row['status'];
            $paymentIntent = sanitize_text_field((string) $row['stripe_payment_intent']);

            EventLogger::dispatch('poradnik_platform_stripe_reconciliation_pending', [
                'order_id'        => $orderId,
                'status'          => $status,
                'payment_intent'  => $paymentIntent,
            ]);

            do_action('poradnik_platform_stripe_reconcile_order', $orderId, $row);
        }

        EventLogger::dispatch('poradnik_platform_stripe_reconciliation_completed', [
            'checked' => count($rows),
        ]);
    }
}
