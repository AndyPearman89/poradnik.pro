<?php

namespace PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure;

class AffiliateRepository
{
    private string $productsTable;
    private string $clicksTable;
    private string $keywordsTable;

    public function __construct()
    {
        global $wpdb;
        $this->productsTable = $wpdb->prefix . 'peartree_affiliate_products';
        $this->clicksTable = $wpdb->prefix . 'peartree_affiliate_clicks';
        $this->keywordsTable = $wpdb->prefix . 'peartree_affiliate_keywords';
    }

    public function getProducts(): array
    {
        $cached = get_transient('ppae_products_all');
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->productsTable} ORDER BY id DESC", ARRAY_A);
        $rows = is_array($rows) ? $rows : [];
        set_transient('ppae_products_all', $rows, 300);

        return $rows;
    }

    public function getProductsPaginated(int $page = 1, int $perPage = 20): array
    {
        global $wpdb;

        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->productsTable} ORDER BY id DESC LIMIT %d OFFSET %d",
                $perPage,
                $offset
            ),
            ARRAY_A
        );

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->productsTable}");

        return [
            'items' => is_array($rows) ? $rows : [],
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil(max(1, $total) / $perPage),
        ];
    }

    public function getProductsByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids), static fn($v): bool => $v > 0));
        if (empty($ids)) {
            return [];
        }

        global $wpdb;
        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = $wpdb->prepare("SELECT * FROM {$this->productsTable} WHERE id IN ({$placeholders})", ...$ids);
        $rows = $wpdb->get_results($sql, ARRAY_A);
        return is_array($rows) ? $rows : [];
    }

    public function getProductById(int $id): ?array
    {
        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->productsTable} WHERE id = %d", $id), ARRAY_A);
        return is_array($row) ? $row : null;
    }

    public function getProductBySlug(string $slug): ?array
    {
        $cacheKey = 'ppae_product_slug_' . md5($slug);
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->productsTable} WHERE slug = %s", $slug), ARRAY_A);
        if (!is_array($row)) {
            return null;
        }

        set_transient($cacheKey, $row, 300);
        return $row;
    }

    public function upsertProduct(array $data, int $id = 0): bool
    {
        global $wpdb;
        $payload = [
            'title' => sanitize_text_field((string) ($data['title'] ?? '')),
            'slug' => sanitize_title((string) ($data['slug'] ?? '')),
            'image' => esc_url_raw((string) ($data['image'] ?? '')),
            'destination_url' => esc_url_raw((string) ($data['destination_url'] ?? '')),
            'price' => sanitize_text_field((string) ($data['price'] ?? '')),
            'rating' => (float) ($data['rating'] ?? 0),
            'description' => sanitize_textarea_field((string) ($data['description'] ?? '')),
            'button_text' => sanitize_text_field((string) ($data['button_text'] ?? 'Sprawdź ofertę')),
            'category' => sanitize_text_field((string) ($data['category'] ?? '')),
            'features' => sanitize_textarea_field((string) ($data['features'] ?? '')),
        ];

        if ($id > 0) {
            $result = $wpdb->update($this->productsTable, $payload, ['id' => $id], ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s'], ['%d']);
        } else {
            $payload['clicks'] = 0;
            $result = $wpdb->insert($this->productsTable, $payload, ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d']);
        }

        $this->flushCaches();
        return $result !== false;
    }

    public function deleteProduct(int $id): bool
    {
        global $wpdb;
        $result = $wpdb->delete($this->productsTable, ['id' => $id], ['%d']);
        $wpdb->delete($this->keywordsTable, ['product_id' => $id], ['%d']);
        $this->flushCaches();

        return $result !== false;
    }

    public function getKeywords(): array
    {
        global $wpdb;
        $rows = $wpdb->get_results(
            "SELECT k.*, p.title AS product_title, p.slug AS product_slug
             FROM {$this->keywordsTable} k
             LEFT JOIN {$this->productsTable} p ON p.id = k.product_id
             ORDER BY k.id DESC",
            ARRAY_A
        );
        return is_array($rows) ? $rows : [];
    }

    public function upsertKeyword(array $data, int $id = 0): bool
    {
        global $wpdb;
        $payload = [
            'keyword' => sanitize_text_field((string) ($data['keyword'] ?? '')),
            'product_id' => (int) ($data['product_id'] ?? 0),
            'created_at' => current_time('mysql'),
        ];

        if ($id > 0) {
            unset($payload['created_at']);
            $result = $wpdb->update($this->keywordsTable, $payload, ['id' => $id], ['%s', '%d'], ['%d']);
        } else {
            $result = $wpdb->insert($this->keywordsTable, $payload, ['%s', '%d', '%s']);
        }

        delete_transient('ppae_keywords_cache');
        return $result !== false;
    }

    public function deleteKeyword(int $id): bool
    {
        global $wpdb;
        $result = $wpdb->delete($this->keywordsTable, ['id' => $id], ['%d']);
        delete_transient('ppae_keywords_cache');
        return $result !== false;
    }

    public function getKeywordsWithProducts(): array
    {
        $cached = get_transient('ppae_keywords_cache');
        if (is_array($cached)) {
            return $cached;
        }

        $rows = $this->getKeywords();
        set_transient('ppae_keywords_cache', $rows, 300);
        return $rows;
    }

    public function trackClick(int $productId, string $ip, string $referrer, string $userAgent): void
    {
        global $wpdb;
        $wpdb->insert(
            $this->clicksTable,
            [
                'product_id' => $productId,
                'date' => current_time('mysql'),
                'ip' => sanitize_text_field($ip),
                'referrer' => esc_url_raw($referrer),
                'user_agent' => sanitize_text_field($userAgent),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        $wpdb->query($wpdb->prepare("UPDATE {$this->productsTable} SET clicks = clicks + 1 WHERE id = %d", $productId));
        delete_transient('ppae_stats_cache');
        $this->flushCaches();
    }

    public function getStatistics(): array
    {
        $cached = get_transient('ppae_stats_cache');
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;

        $topProducts = $wpdb->get_results(
            "SELECT id, title, slug, clicks FROM {$this->productsTable} ORDER BY clicks DESC LIMIT 10",
            ARRAY_A
        );

        $trends = $wpdb->get_results(
            "SELECT DATE(date) as day, COUNT(*) as clicks
             FROM {$this->clicksTable}
             WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(date)
             ORDER BY day DESC",
            ARRAY_A
        );

        $bestKeywords = $wpdb->get_results(
            "SELECT k.keyword, SUM(p.clicks) AS total_clicks
             FROM {$this->keywordsTable} k
             LEFT JOIN {$this->productsTable} p ON p.id = k.product_id
             GROUP BY k.keyword
             ORDER BY total_clicks DESC
             LIMIT 10",
            ARRAY_A
        );

        $stats = [
            'top_products' => is_array($topProducts) ? $topProducts : [],
            'click_trends' => is_array($trends) ? $trends : [],
            'best_keywords' => is_array($bestKeywords) ? $bestKeywords : [],
        ];

        set_transient('ppae_stats_cache', $stats, 300);
        return $stats;
    }

    public function getOverviewMetrics(): array
    {
        $cacheKey = 'ppae_overview_metrics';
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;

        $productsCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->productsTable}");
        $keywordsCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->keywordsTable}");
        $clicks30d = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$this->clicksTable} WHERE date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        $clicksTotal = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->clicksTable}");

        $metrics = [
            'products_count' => $productsCount,
            'keywords_count' => $keywordsCount,
            'clicks_30d' => $clicks30d,
            'clicks_total' => $clicksTotal,
            'avg_daily_clicks_30d' => round($clicks30d / 30, 2),
        ];

        set_transient($cacheKey, $metrics, 300);
        return $metrics;
    }

    public function getRecentActivity(int $limit = 12): array
    {
        global $wpdb;

        $limit = max(1, min(100, $limit));

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT c.date, c.referrer, p.id AS product_id, p.title AS product_title
                 FROM {$this->clicksTable} c
                 LEFT JOIN {$this->productsTable} p ON p.id = c.product_id
                 ORDER BY c.date DESC
                 LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        return is_array($rows) ? $rows : [];
    }

    public function clearAllCaches(): void
    {
        $this->flushCaches();
        delete_transient('ppae_keywords_cache');
        delete_transient('ppae_overview_metrics');
    }

    public function flushCaches(): void
    {
        delete_transient('ppae_products_all');
        delete_transient('ppae_stats_cache');
        delete_transient('ppae_overview_metrics');
    }
}
