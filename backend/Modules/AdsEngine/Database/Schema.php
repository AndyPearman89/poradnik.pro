<?php

namespace Poradnik\Platform\Modules\AdsEngine\Database;

use Poradnik\Platform\Modules\AdsEngine\Support\Db;

if (! defined('ABSPATH')) {
    exit;
}

final class Schema
{
    private const VERSION = '2.0.0';
    private const VERSION_KEY = 'poradnik_platform_ads_engine_schema_version';

    public static function maybeMigrate(): void
    {
        if ((string) get_option(self::VERSION_KEY, '') === self::VERSION) {
            return;
        }

        self::migrate();
        self::seedSlots();
        self::seedPricing();
        update_option(self::VERSION_KEY, self::VERSION, false);
    }

    private static function migrate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $sql = [];

        $sql[] = "CREATE TABLE " . Db::table('advertiser_campaigns') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            campaign_name VARCHAR(191) NOT NULL,
            campaign_type VARCHAR(100) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'draft',
            start_date DATETIME NULL,
            end_date DATETIME NULL,
            budget DECIMAL(12,2) NOT NULL DEFAULT 0,
            slot_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY status (status),
            KEY slot_id (slot_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE " . Db::table('ad_slots') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            slot_name VARCHAR(100) NOT NULL,
            template VARCHAR(100) NOT NULL,
            price DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL DEFAULT 'active',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slot_name (slot_name),
            KEY template (template),
            KEY status (status)
        ) {$charset};";

        $sql[] = "CREATE TABLE " . Db::table('ads') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            campaign_id BIGINT UNSIGNED NOT NULL,
            slot_id BIGINT UNSIGNED NOT NULL,
            creative_url TEXT NULL,
            target_url TEXT NULL,
            clicks BIGINT UNSIGNED NOT NULL DEFAULT 0,
            impressions BIGINT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY campaign_id (campaign_id),
            KEY slot_id (slot_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE " . Db::table('payments') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            gateway VARCHAR(50) NOT NULL DEFAULT 'manual',
            campaign_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY campaign_id (campaign_id),
            KEY status (status),
            KEY gateway (gateway)
        ) {$charset};";

        $sql[] = "CREATE TABLE " . Db::table('invoices') . " (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            invoice_number VARCHAR(100) NOT NULL,
            amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            company_name VARCHAR(191) NULL,
            vat_number VARCHAR(100) NULL,
            address TEXT NULL,
            tax DECIMAL(12,2) NOT NULL DEFAULT 0,
            payment_id BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY invoice_number (invoice_number),
            KEY user_id (user_id),
            KEY payment_id (payment_id),
            KEY status (status)
        ) {$charset};";

        foreach ($sql as $statement) {
            dbDelta($statement);
        }
    }

    private static function seedSlots(): void
    {
        global $wpdb;
        $table = Db::table('ad_slots');
        $now = current_time('mysql', true);

        $slots = [
            ['AD_SLOT_HERO', 'frontpage', 500],
            ['AD_SLOT_SIDEBAR', 'global', 200],
            ['AD_SLOT_ARTICLE_TOP', 'guide', 250],
            ['AD_SLOT_ARTICLE_MIDDLE', 'guide', 300],
            ['AD_SLOT_ARTICLE_BOTTOM', 'guide', 250],
            ['AD_SLOT_FOOTER', 'global', 150],
        ];

        foreach ($slots as [$name, $template, $price]) {
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE slot_name = %s", $name));
            if ($exists) {
                continue;
            }

            $wpdb->insert(
                $table,
                [
                    'slot_name' => $name,
                    'template' => $template,
                    'price' => $price,
                    'status' => 'active',
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                ['%s', '%s', '%f', '%s', '%s', '%s']
            );
        }
    }

    private static function seedPricing(): void
    {
        if (get_option('poradnik_ads_pricing_engine') !== false) {
            return;
        }

        add_option('poradnik_ads_pricing_engine', [
            'homepage_banner_7' => 500,
            'homepage_banner_30' => 1500,
            'sidebar_banner_7' => 200,
            'sidebar_banner_30' => 600,
            'sponsored_article' => 1500,
            'featured_ranking' => 800,
        ], '', false);
    }
}
