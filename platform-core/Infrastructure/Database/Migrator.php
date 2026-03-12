<?php

namespace Poradnik\Platform\Infrastructure\Database;

use Poradnik\Platform\Core\EventLogger;

if (! defined('ABSPATH')) {
    exit;
}

final class Migrator
{
    private const OPTION_KEY = 'poradnik_platform_db_version';
    private const SCHEMA_VERSION = '1.2.0';

    public static function init(): void
    {
        add_action('init', [self::class, 'maybeMigrate'], 2);
    }

    public static function maybeMigrate(): void
    {
        $installedVersion = get_option(self::OPTION_KEY, '0.0.0');

        if (! is_string($installedVersion)) {
            $installedVersion = '0.0.0';
        }

        if (version_compare($installedVersion, self::SCHEMA_VERSION, '>=')) {
            return;
        }

        self::runMigrations();
        update_option(self::OPTION_KEY, self::SCHEMA_VERSION, false);

        EventLogger::dispatch(
            'poradnik_platform_db_migrated',
            [
                'version' => self::SCHEMA_VERSION,
            ]
        );
    }

    public static function tableName(string $table): string
    {
        global $wpdb;

        return $wpdb->prefix . 'poradnik_' . $table;
    }

    private static function runMigrations(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charsetCollate = $wpdb->get_charset_collate();

        foreach (self::schema($charsetCollate) as $statement) {
            dbDelta($statement);
        }
    }

    /**
     * @return array<int, string>
     */
    private static function schema(string $charsetCollate): array
    {
        return [
            "CREATE TABLE " . self::tableName('affiliate_products') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name varchar(191) NOT NULL,
                slug varchar(191) NOT NULL,
                affiliate_url text NOT NULL,
                category_id bigint(20) unsigned DEFAULT NULL,
                status varchar(50) NOT NULL DEFAULT 'draft',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY slug (slug),
                KEY category_id (category_id),
                KEY status (status)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('affiliate_clicks') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                product_id bigint(20) unsigned NOT NULL,
                post_id bigint(20) unsigned DEFAULT NULL,
                source varchar(191) DEFAULT '',
                referrer varchar(255) DEFAULT '',
                user_ip varchar(45) DEFAULT '',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY product_id (product_id),
                KEY post_id (post_id),
                KEY source (source),
                KEY created_at (created_at)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('affiliate_categories') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name varchar(191) NOT NULL,
                slug varchar(191) NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY slug (slug)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('ad_campaigns') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                name varchar(191) NOT NULL,
                advertiser_id bigint(20) unsigned DEFAULT NULL,
                slot_id bigint(20) unsigned DEFAULT NULL,
                status varchar(50) NOT NULL DEFAULT 'draft',
                start_date datetime DEFAULT NULL,
                end_date datetime DEFAULT NULL,
                budget decimal(12,2) DEFAULT 0.00,
                destination_url text DEFAULT NULL,
                creative_text text DEFAULT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY advertiser_id (advertiser_id),
                KEY slot_id (slot_id),
                KEY status (status),
                KEY start_date (start_date),
                KEY end_date (end_date)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('ad_slots') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                slot_key varchar(191) NOT NULL,
                label varchar(191) NOT NULL,
                location varchar(191) DEFAULT '',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY slot_key (slot_key),
                KEY location (location)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('ad_clicks') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                campaign_id bigint(20) unsigned NOT NULL,
                slot_id bigint(20) unsigned DEFAULT NULL,
                source varchar(191) DEFAULT '',
                user_ip varchar(45) DEFAULT '',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY campaign_id (campaign_id),
                KEY slot_id (slot_id),
                KEY source (source),
                KEY created_at (created_at)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('ad_impressions') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                campaign_id bigint(20) unsigned NOT NULL,
                slot_id bigint(20) unsigned DEFAULT NULL,
                source varchar(191) DEFAULT '',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY campaign_id (campaign_id),
                KEY slot_id (slot_id),
                KEY source (source),
                KEY created_at (created_at)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('sponsored_articles') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                post_id bigint(20) unsigned DEFAULT NULL,
                advertiser_id bigint(20) unsigned DEFAULT NULL,
                advertiser_email varchar(191) DEFAULT NULL,
                title varchar(255) DEFAULT NULL,
                content longtext,
                package_key varchar(100) NOT NULL DEFAULT 'basic',
                status varchar(50) NOT NULL DEFAULT 'pending',
                payment_status varchar(50) NOT NULL DEFAULT 'pending',
                amount decimal(12,2) DEFAULT 0.00,
                currency varchar(10) DEFAULT 'PLN',
                stripe_payment_intent varchar(191) DEFAULT NULL,
                desired_publish_at datetime DEFAULT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY post_id (post_id),
                KEY advertiser_id (advertiser_id),
                KEY advertiser_email (advertiser_email),
                KEY package_key (package_key),
                KEY status (status),
                KEY payment_status (payment_status)
            ) {$charsetCollate};",
        ];
    }
}
