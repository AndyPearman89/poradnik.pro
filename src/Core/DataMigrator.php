<?php

namespace PearTree\ProgrammaticAffiliate\Core;

class DataMigrator
{
    private const MIGRATION_OPTION = 'ppae_migration_from_paa_done';

    public function maybeMigrate(): void
    {
        if ((int) get_option(self::MIGRATION_OPTION, 0) === 1) {
            return;
        }

        global $wpdb;

        $productsTable = $wpdb->prefix . 'peartree_affiliate_products';
        $linksTable = $wpdb->prefix . 'peartree_affiliate_links';
        $clicksTable = $wpdb->prefix . 'peartree_affiliate_clicks';

        $productIdMap = [];

        if ($this->tableExists($linksTable)) {
            $links = $wpdb->get_results("SELECT * FROM {$linksTable}", ARRAY_A);
            if (is_array($links)) {
                foreach ($links as $link) {
                    $oldId = (int) ($link['id'] ?? 0);
                    if ($oldId <= 0) {
                        continue;
                    }

                    $slug = sanitize_title((string) ($link['slug'] ?? ''));
                    if ($slug === '') {
                        $slug = sanitize_title((string) ($link['title'] ?? 'oferta-' . $oldId));
                    }

                    $existingId = (int) $wpdb->get_var($wpdb->prepare(
                        "SELECT id FROM {$productsTable} WHERE slug = %s LIMIT 1",
                        $slug
                    ));

                    if ($existingId > 0) {
                        $productIdMap[$oldId] = $existingId;
                        continue;
                    }

                    $inserted = $wpdb->insert(
                        $productsTable,
                        [
                            'title' => sanitize_text_field((string) ($link['title'] ?? 'Oferta')),
                            'slug' => $slug,
                            'image' => esc_url_raw((string) ($link['image_url'] ?? '')),
                            'destination_url' => esc_url_raw((string) ($link['destination_url'] ?? '')),
                            'price' => '',
                            'rating' => 0,
                            'description' => sanitize_textarea_field((string) ($link['description'] ?? '')),
                            'button_text' => sanitize_text_field((string) ($link['button_text'] ?? 'Sprawdź ofertę')),
                            'category' => sanitize_text_field((string) ($link['category'] ?? '')),
                            'features' => '',
                            'clicks' => (int) ($link['clicks'] ?? 0),
                        ],
                        ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d']
                    );

                    if ($inserted !== false) {
                        $productIdMap[$oldId] = (int) $wpdb->insert_id;
                    }
                }
            }
        }

        if ($this->tableExists($clicksTable)) {
            $columns = $this->getTableColumns($clicksTable);

            if (!in_array('product_id', $columns, true)) {
                $wpdb->query("ALTER TABLE {$clicksTable} ADD COLUMN product_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0");
                $columns[] = 'product_id';
            }

            if (in_array('affiliate_id', $columns, true)) {
                $rows = $wpdb->get_results("SELECT id, affiliate_id, product_id FROM {$clicksTable}", ARRAY_A);
                if (is_array($rows)) {
                    foreach ($rows as $row) {
                        $clickId = (int) ($row['id'] ?? 0);
                        $affiliateId = (int) ($row['affiliate_id'] ?? 0);
                        $productId = (int) ($row['product_id'] ?? 0);
                        if ($clickId <= 0 || $productId > 0 || $affiliateId <= 0) {
                            continue;
                        }

                        $mappedProductId = (int) ($productIdMap[$affiliateId] ?? 0);
                        if ($mappedProductId <= 0) {
                            continue;
                        }

                        $wpdb->update(
                            $clicksTable,
                            ['product_id' => $mappedProductId],
                            ['id' => $clickId],
                            ['%d'],
                            ['%d']
                        );
                    }
                }
            }

            $totals = $wpdb->get_results(
                "SELECT product_id, COUNT(*) AS total_clicks
                 FROM {$clicksTable}
                 WHERE product_id > 0
                 GROUP BY product_id",
                ARRAY_A
            );

            if (is_array($totals)) {
                foreach ($totals as $total) {
                    $wpdb->update(
                        $productsTable,
                        ['clicks' => (int) ($total['total_clicks'] ?? 0)],
                        ['id' => (int) ($total['product_id'] ?? 0)],
                        ['%d'],
                        ['%d']
                    );
                }
            }
        }

        $this->migrateLegacySettings();
        $this->migrateLegacySingleAffiliateSettings($productsTable);

        update_option(self::MIGRATION_OPTION, 1, false);
        delete_transient('ppae_products_all');
        delete_transient('ppae_stats_cache');
        delete_transient('ppae_keywords_cache');
    }

    private function migrateLegacySettings(): void
    {
        $legacy = get_option('paa_adsense_settings', []);
        $legacy = is_array($legacy) ? $legacy : [];

        $current = get_option('ppae_adsense_settings', []);
        $current = is_array($current) ? $current : [];

        $currentPublisher = (string) ($current['publisher_id'] ?? '');
        $currentScript = (string) ($current['script'] ?? '');

        if ($currentPublisher !== '' || $currentScript !== '') {
            return;
        }

        $legacyScript = (string) ($legacy['adsense_script'] ?? '');
        $legacyPublisher = '';

        if (preg_match('/ca-pub-[0-9]+/', $legacyScript, $matches) === 1) {
            $legacyPublisher = (string) ($matches[0] ?? '');
        }

        if ($legacyScript === '' && $legacyPublisher === '') {
            return;
        }

        update_option('ppae_adsense_settings', [
            'publisher_id' => sanitize_text_field($legacyPublisher),
            'script' => $legacyScript,
            'auto_ads' => !empty($legacy['auto_ads']) ? 1 : 0,
        ]);
    }

    private function migrateLegacySingleAffiliateSettings(string $productsTable): void
    {
        global $wpdb;

        $legacy = get_option('paa_settings', []);
        if (!is_array($legacy) || empty($legacy['affiliate_enabled'])) {
            return;
        }

        $destinationUrl = esc_url_raw((string) ($legacy['affiliate_url'] ?? ''));
        if ($destinationUrl === '') {
            return;
        }

        $existingCount = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$productsTable}");
        if ($existingCount > 0) {
            return;
        }

        $title = sanitize_text_field((string) ($legacy['affiliate_label'] ?? 'Oferta partnerska'));
        $slug = sanitize_title($title !== '' ? $title : 'oferta-partnerska');

        $wpdb->insert(
            $productsTable,
            [
                'title' => $title !== '' ? $title : 'Oferta partnerska',
                'slug' => $slug,
                'image' => '',
                'destination_url' => $destinationUrl,
                'price' => '',
                'rating' => 0,
                'description' => '',
                'button_text' => $title !== '' ? $title : 'Sprawdź ofertę',
                'category' => 'legacy',
                'features' => '',
                'clicks' => (int) get_option('paa_affiliate_clicks', 0),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%d']
        );
    }

    private function tableExists(string $table): bool
    {
        global $wpdb;
        $found = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
        return is_string($found) && $found === $table;
    }

    private function getTableColumns(string $table): array
    {
        global $wpdb;
        $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table}", 0);
        return is_array($columns) ? $columns : [];
    }
}
