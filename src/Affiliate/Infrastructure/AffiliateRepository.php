<?php

namespace Poradnik\AfilacjaAdsense\Affiliate\Infrastructure;

use Poradnik\AfilacjaAdsense\Affiliate\Domain\AffiliateLink;

class AffiliateRepository
{
    private string $linksTable;
    private string $clicksTable;
    private ?string $clickForeignKey = null;

    public function __construct()
    {
        global $wpdb;
        $this->linksTable = $wpdb->prefix . 'peartree_affiliate_links';
        $this->clicksTable = $wpdb->prefix . 'peartree_affiliate_clicks';
    }

    public function getAll(): array
    {
        $cacheKey = 'paa_affiliate_links_all';
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $rows = $wpdb->get_results("SELECT * FROM {$this->linksTable} ORDER BY id DESC", ARRAY_A);
        $rows = is_array($rows) ? $rows : [];
        set_transient($cacheKey, $rows, 300);

        return $rows;
    }

    public function findById(int $id): ?AffiliateLink
    {
        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->linksTable} WHERE id = %d", $id),
            ARRAY_A
        );

        return is_array($row) ? new AffiliateLink($row) : null;
    }

    public function findBySlug(string $slug): ?AffiliateLink
    {
        $cacheKey = 'paa_affiliate_slug_' . md5($slug);
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return new AffiliateLink($cached);
        }

        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->linksTable} WHERE slug = %s", $slug),
            ARRAY_A
        );

        if (!is_array($row)) {
            return null;
        }

        set_transient($cacheKey, $row, 300);
        return new AffiliateLink($row);
    }

    public function insert(array $data): int
    {
        global $wpdb;

        $wpdb->insert(
            $this->linksTable,
            [
                'title' => sanitize_text_field((string) ($data['title'] ?? '')),
                'slug' => sanitize_title((string) ($data['slug'] ?? '')),
                'destination_url' => esc_url_raw((string) ($data['destination_url'] ?? '')),
                'category' => sanitize_text_field((string) ($data['category'] ?? '')),
                'description' => sanitize_textarea_field((string) ($data['description'] ?? '')),
                'button_text' => sanitize_text_field((string) ($data['button_text'] ?? 'Sprawdź ofertę')),
                'image_url' => esc_url_raw((string) ($data['image_url'] ?? '')),
                'clicks' => 0,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d']
        );

        $this->flushCache();
        return (int) $wpdb->insert_id;
    }

    public function update(int $id, array $data): bool
    {
        global $wpdb;
        $updated = $wpdb->update(
            $this->linksTable,
            [
                'title' => sanitize_text_field((string) ($data['title'] ?? '')),
                'slug' => sanitize_title((string) ($data['slug'] ?? '')),
                'destination_url' => esc_url_raw((string) ($data['destination_url'] ?? '')),
                'category' => sanitize_text_field((string) ($data['category'] ?? '')),
                'description' => sanitize_textarea_field((string) ($data['description'] ?? '')),
                'button_text' => sanitize_text_field((string) ($data['button_text'] ?? 'Sprawdź ofertę')),
                'image_url' => esc_url_raw((string) ($data['image_url'] ?? '')),
            ],
            ['id' => $id],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s'],
            ['%d']
        );

        $this->flushCache();
        return $updated !== false;
    }

    public function delete(int $id): bool
    {
        global $wpdb;
        $deleted = $wpdb->delete($this->linksTable, ['id' => $id], ['%d']);
        $this->flushCache();

        return $deleted !== false;
    }

    public function trackClick(int $affiliateId, string $ip, string $userAgent, string $referrer): void
    {
        global $wpdb;

        $foreignKey = $this->resolveClicksForeignKey();
        if ($foreignKey === null) {
            return;
        }

        $wpdb->insert(
            $this->clicksTable,
            [
                $foreignKey => $affiliateId,
                'date' => current_time('mysql'),
                'ip' => sanitize_text_field($ip),
                'user_agent' => sanitize_text_field($userAgent),
                'referrer' => esc_url_raw($referrer),
            ],
            ['%d', '%s', '%s', '%s', '%s']
        );

        $wpdb->query($wpdb->prepare("UPDATE {$this->linksTable} SET clicks = clicks + 1 WHERE id = %d", $affiliateId));
        delete_transient('paa_affiliate_stats');
    }

    public function getStats(): array
    {
        $cacheKey = 'paa_affiliate_stats';
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        global $wpdb;
        $totalLinks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->linksTable}");
        $totalClicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$this->clicksTable}");

        $topLinks = $wpdb->get_results(
            "SELECT id, title, slug, clicks FROM {$this->linksTable} ORDER BY clicks DESC LIMIT 10",
            ARRAY_A
        );

        $stats = [
            'total_links' => $totalLinks,
            'total_clicks' => $totalClicks,
            'top_links' => is_array($topLinks) ? $topLinks : [],
        ];

        set_transient($cacheKey, $stats, 300);
        return $stats;
    }

    public function flushCache(): void
    {
        delete_transient('paa_affiliate_links_all');
        delete_transient('paa_affiliate_stats');
    }

    private function resolveClicksForeignKey(): ?string
    {
        if ($this->clickForeignKey !== null) {
            return $this->clickForeignKey;
        }

        global $wpdb;

        $affiliateColumn = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$this->clicksTable} LIKE %s", 'affiliate_id'));
        if (is_string($affiliateColumn) && $affiliateColumn !== '') {
            $this->clickForeignKey = 'affiliate_id';
            return $this->clickForeignKey;
        }

        $productColumn = $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$this->clicksTable} LIKE %s", 'product_id'));
        if (is_string($productColumn) && $productColumn !== '') {
            $this->clickForeignKey = 'product_id';
            return $this->clickForeignKey;
        }

        return null;
    }
}
