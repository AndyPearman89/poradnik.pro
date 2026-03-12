<?php

namespace Poradnik\Platform\Domain\Affiliate;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class ProductRepository
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function findAll(): array
    {
        global $wpdb;

        $table = Migrator::tableName('affiliate_products');
        $results = $wpdb->get_results("SELECT * FROM {$table} ORDER BY id DESC", ARRAY_A);

        return is_array($results) ? $results : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findById(int $productId): ?array
    {
        global $wpdb;

        if ($productId < 1) {
            return null;
        }

        $table = Migrator::tableName('affiliate_products');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d LIMIT 1", $productId);
        $result = $wpdb->get_row($query, ARRAY_A);

        return is_array($result) ? $result : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findBySlug(string $slug): ?array
    {
        global $wpdb;

        $slug = sanitize_title($slug);
        if ($slug === '') {
            return null;
        }

        $table = Migrator::tableName('affiliate_products');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE slug = %s LIMIT 1", $slug);
        $result = $wpdb->get_row($query, ARRAY_A);

        return is_array($result) ? $result : null;
    }

    /**
     * @param array<int, int> $ids
     * @return array<int, array<string, mixed>>
     */
    public static function findByIds(array $ids): array
    {
        global $wpdb;

        $ids = array_values(array_filter(array_map('absint', $ids)));
        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '%d'));
        $table = Migrator::tableName('affiliate_products');
        $query = $wpdb->prepare("SELECT * FROM {$table} WHERE id IN ({$placeholders}) ORDER BY FIELD(id, {$placeholders})", array_merge($ids, $ids));
        $results = $wpdb->get_results($query, ARRAY_A);

        return is_array($results) ? $results : [];
    }

    public static function save(array $data, int $productId = 0): int
    {
        global $wpdb;

        $table = Migrator::tableName('affiliate_products');
        $now = current_time('mysql', true);

        $payload = [
            'name' => sanitize_text_field((string) ($data['name'] ?? '')),
            'slug' => sanitize_title((string) ($data['slug'] ?? '')),
            'affiliate_url' => esc_url_raw((string) ($data['affiliate_url'] ?? '')),
            'category_id' => absint($data['category_id'] ?? 0),
            'status' => sanitize_key((string) ($data['status'] ?? 'draft')),
            'updated_at' => $now,
        ];

        if ($payload['slug'] === '' && $payload['name'] !== '') {
            $payload['slug'] = sanitize_title($payload['name']);
        }

        if ($productId > 0) {
            $updated = $wpdb->update(
                $table,
                $payload,
                ['id' => $productId],
                ['%s', '%s', '%s', '%d', '%s', '%s'],
                ['%d']
            );

            if ($updated === false) {
                return 0;
            }

            return $productId;
        }

        $payload['created_at'] = $now;

        $inserted = $wpdb->insert(
            $table,
            $payload,
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
        );

        if ($inserted !== 1) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    public static function delete(int $productId): bool
    {
        global $wpdb;

        if ($productId < 1) {
            return false;
        }

        $table = Migrator::tableName('affiliate_products');
        $deleted = $wpdb->delete($table, ['id' => $productId], ['%d']);

        return $deleted === 1;
    }
}
