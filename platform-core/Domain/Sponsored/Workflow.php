<?php

namespace Poradnik\Platform\Domain\Sponsored;

use Poradnik\Platform\Core\EventLogger;

if (! defined('ABSPATH')) {
    exit;
}

final class Workflow
{
    public static function submit(array $data): int
    {
        $orderId = OrderRepository::create(array_merge($data, [
            'status' => 'submitted',
            'payment_status' => 'pending',
        ]));

        if ($orderId > 0) {
            EventLogger::dispatch('poradnik_platform_sponsored_submitted', ['order_id' => $orderId]);
        }

        return $orderId;
    }

    public static function review(int $orderId): bool
    {
        $updated = OrderRepository::updateStatus($orderId, 'review', 'pending');

        if ($updated) {
            EventLogger::dispatch('poradnik_platform_sponsored_reviewed', ['order_id' => $orderId]);
        }

        return $updated;
    }

    public static function markPaid(int $orderId, string $paymentIntent = ''): bool
    {
        $updated = OrderRepository::updateStatus($orderId, 'paid', 'paid');

        if ($updated) {
            EventLogger::dispatch('poradnik_platform_sponsored_paid', ['order_id' => $orderId, 'payment_intent' => sanitize_text_field($paymentIntent)]);
        }

        return $updated;
    }

    public static function publish(int $orderId): bool
    {
        $order = OrderRepository::findById($orderId);
        if (! is_array($order)) {
            return false;
        }

        $title = (string) ($order['title'] ?? 'Sponsored Article');
        $content = (string) ($order['content'] ?? '');

        $postId = wp_insert_post([
            'post_type' => 'sponsored',
            'post_status' => 'publish',
            'post_title' => $title,
            'post_content' => $content,
        ], true);

        if (is_wp_error($postId) || ! is_int($postId) || $postId < 1) {
            return false;
        }

        update_post_meta($postId, '_poradnik_sponsored_badge', '1');

        $attached = OrderRepository::attachPost($orderId, $postId);
        $updated = OrderRepository::updateStatus($orderId, 'published', 'paid');

        if ($attached && $updated) {
            EventLogger::dispatch('poradnik_platform_sponsored_published', ['order_id' => $orderId, 'post_id' => $postId]);
            return true;
        }

        return false;
    }
}
