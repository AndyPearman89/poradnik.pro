<?php

namespace PearTree\ProgrammaticAffiliate\SEO\Infrastructure;

class SeoPageRepository
{
    private string $table;

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'peartree_seo_pages';
    }

    public function all(): array
    {
        $cached = get_transient('ppae_seo_pages_all');
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY id DESC", ARRAY_A);
        $rows = is_array($rows) ? $rows : [];

        set_transient('ppae_seo_pages_all', $rows, 300);
        return $rows;
    }

    public function allPaginated(int $page = 1, int $perPage = 20): array
    {
        global $wpdb;

        $page = max(1, $page);
        $perPage = max(1, min(200, $perPage));
        $offset = ($page - 1) * $perPage;

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT %d OFFSET %d",
                $perPage,
                $offset
            ),
            ARRAY_A
        );

        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");

        return [
            'items' => is_array($rows) ? $rows : [],
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil(max(1, $total) / $perPage),
        ];
    }

    public function countAll(): int
    {
        global $wpdb;
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
    }

    public function findBySlug(string $slug): ?array
    {
        $cacheKey = 'ppae_seo_page_' . md5($slug);
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table} WHERE slug = %s", $slug), ARRAY_A);
        if (!is_array($row)) {
            return null;
        }

        set_transient($cacheKey, $row, 300);
        return $row;
    }

    public function insert(array $data): int
    {
        global $wpdb;
        $wpdb->insert(
            $this->table,
            [
                'keyword' => sanitize_text_field((string) ($data['keyword'] ?? '')),
                'slug' => sanitize_title((string) ($data['slug'] ?? '')),
                'title' => sanitize_text_field((string) ($data['title'] ?? '')),
                'content_template' => wp_kses_post((string) ($data['content_template'] ?? '')),
                'category' => sanitize_text_field((string) ($data['category'] ?? '')),
                'wp_page_id' => (int) ($data['wp_page_id'] ?? 0),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%d']
        );

        delete_transient('ppae_seo_pages_all');
        return (int) $wpdb->insert_id;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $result = $wpdb->delete($this->table, ['id' => $id], ['%d']);
        delete_transient('ppae_seo_pages_all');
        return $result !== false;
    }

    public function clearCaches(): void
    {
        delete_transient('ppae_seo_pages_all');
    }
}
