<?php

namespace Poradnik\AfilacjaAdsense\Core;

class Kernel
{
    public static function activate(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();
        $linksTable = $wpdb->prefix . 'peartree_affiliate_links';
        $clicksTable = $wpdb->prefix . 'peartree_affiliate_clicks';

        $sqlLinks = "CREATE TABLE {$linksTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(191) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            destination_url TEXT NOT NULL,
            category VARCHAR(100) DEFAULT '' NOT NULL,
            description TEXT NULL,
            button_text VARCHAR(100) DEFAULT '' NOT NULL,
            image_url TEXT NULL,
            clicks BIGINT(20) UNSIGNED DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) {$charsetCollate};";

        $sqlClicks = "CREATE TABLE {$clicksTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            affiliate_id BIGINT(20) UNSIGNED NOT NULL,
            date DATETIME NOT NULL,
            ip VARCHAR(100) DEFAULT '' NOT NULL,
            user_agent TEXT NULL,
            referrer TEXT NULL,
            PRIMARY KEY  (id),
            KEY affiliate_id (affiliate_id),
            KEY date (date)
        ) {$charsetCollate};";

        dbDelta($sqlLinks);
        dbDelta($sqlClicks);

        add_option('paa_adsense_settings', [
            'publisher_id' => '',
            'adsense_script' => '',
            'auto_ads' => 0,
        ]);

        add_option('paa_db_version', '1.0.0');

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
        add_rewrite_rule('^go/([^/]+)/?$', 'index.php?paa_go_slug=$matches[1]', 'top');
    }
}
