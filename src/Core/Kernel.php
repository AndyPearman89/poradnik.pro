<?php

namespace PearTree\ProgrammaticAffiliate\Core;

class Kernel
{
    public static function activate(): void
    {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $productsTable = $wpdb->prefix . 'peartree_affiliate_products';
        $clicksTable = $wpdb->prefix . 'peartree_affiliate_clicks';
        $keywordsTable = $wpdb->prefix . 'peartree_affiliate_keywords';
        $seoPagesTable = $wpdb->prefix . 'peartree_seo_pages';

        dbDelta("CREATE TABLE {$productsTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            image TEXT NULL,
            destination_url TEXT NOT NULL,
            price VARCHAR(100) DEFAULT '' NOT NULL,
            rating DECIMAL(3,2) DEFAULT 0.00 NOT NULL,
            description TEXT NULL,
            button_text VARCHAR(100) DEFAULT '' NOT NULL,
            category VARCHAR(100) DEFAULT '' NOT NULL,
            features TEXT NULL,
            clicks BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY category (category)
        ) {$charset};");

        dbDelta("CREATE TABLE {$clicksTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            date DATETIME NOT NULL,
            ip VARCHAR(100) DEFAULT '' NOT NULL,
            referrer TEXT NULL,
            user_agent TEXT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id),
            KEY date (date)
        ) {$charset};");

        dbDelta("CREATE TABLE {$keywordsTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            keyword VARCHAR(191) NOT NULL,
            product_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY keyword (keyword),
            KEY product_id (product_id)
        ) {$charset};");

        dbDelta("CREATE TABLE {$seoPagesTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            keyword VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            title VARCHAR(191) NOT NULL,
            content_template LONGTEXT NULL,
            category VARCHAR(100) DEFAULT '' NOT NULL,
            wp_page_id BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug),
            KEY category (category)
        ) {$charset};");

        add_option('ppae_adsense_settings', [
            'publisher_id' => '',
            'script' => '',
            'auto_ads' => 0,
        ]);
        add_option('ppae_general_settings', [
            'autolink_enabled' => 1,
        ]);

        (new self())->registerRewrite();
        flush_rewrite_rules();
    }

    public static function deactivate(): void
    {
        flush_rewrite_rules();
    }

    public function boot(): void
    {
        (new ServiceProvider())->register();
    }

    public function registerRewrite(): void
    {
        add_rewrite_rule('^go/([^/]+)/?$', 'index.php?ppae_go_slug=$matches[1]', 'top');
    }
}
