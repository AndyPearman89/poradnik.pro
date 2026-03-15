<?php

namespace Poradnik\Platform\Domain\Sponsored;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class OrderRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function findAll(): array
    {
        global $wpdb;

        $table = Migrator::tableName('sponsored_articles');
        $results = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A);

        return is_array($results) ? $results : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findById(int $orderId): ?array
    {
        global $wpdb;

        if ($orderId < 1) {
            return null;
        }

        $table = Migrator::tableName('sponsored_articles');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $orderId);
        $result = $wpdb->get_row($query, ARRAY_A);

        return is_array($result) ? $result : null;
    }

    public static function create(array $data): int
    {
        global $wpdb;

        $table = Migrator::tableName('sponsored_articles');
        $now = current_time('mysql', true);

        $payload = [
            'post_id' => absint($data['post_id'] ?? 0),
            'advertiser_id' => absint($data['advertiser_id'] ?? 0),
            'advertiser_email' => sanitize_email((string) ($data['advertiser_email'] ?? '')),
            'title' => sanitize_text_field((string) ($data['title'] ?? '')),
            'content' => wp_kses_post((string) ($data['content'] ?? '')),
            'package_key' => sanitize_key((string) ($data['package_key'] ?? 'basic')),
            'status' => sanitize_key((string) ($data['status'] ?? 'pending')),
            'payment_status' => sanitize_key((string) ($data['payment_status'] ?? 'pending')),
            'amount' => is_numeric($data['amount'] ?? null) ? (float) $data['amount'] : 0,
            'currency' => strtoupper(sanitize_text_field((string) ($data['currency'] ?? 'PLN'))),
            'stripe_payment_intent' => sanitize_text_field((string) ($data['stripe_payment_intent'] ?? '')),
            'desired_publish_at' => self::normalizeDate((string) ($data['desired_publish_at'] ?? '')),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $inserted = $wpdb->insert(
            $table,
            $payload,
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s']
        );

        if ($inserted !== 1) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public static function updateStatus(int $orderId, string $status, string $paymentStatus = ''): bool
    {
        global $wpdb;

        if ($orderId < 1) {
            return false;
        }

        $table = Migrator::tableName('sponsored_articles');
        $payload = [
            'status' => sanitize_key($status),
            'updated_at' => current_time('mysql', true),
        ];
        $format = ['%s', '%s'];

        if ($paymentStatus !== '') {
            $payload['payment_status'] = sanitize_key($paymentStatus);
            $format[] = '%s';
        }

        $updated = $wpdb->update($table, $payload, ['id' => $orderId], $format, ['%d']);

        return $updated !== false;
    }

    public static function attachPost(int $orderId, int $postId): bool
    {
        global $wpdb;

        if ($orderId < 1 || $postId < 1) {
            return false;
        }

        $table = Migrator::tableName('sponsored_articles');
        $updated = $wpdb->update(
            $table,
            [
                'post_id' => $postId,
                'updated_at' => current_time('mysql', true),
            ],
            ['id' => $orderId],
            ['%d', '%s'],
            ['%d']
        );

        return $updated !== false;
    }

    private static function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}
