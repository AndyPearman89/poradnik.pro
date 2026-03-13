<?php

namespace Poradnik\Platform\Infrastructure\Database;

use Poradnik\Platform\Core\EventLogger;

if (! defined('ABSPATH')) {
    exit;
}

final class Migrator
{
    private const OPTION_KEY = 'poradnik_platform_db_version';
    private const SCHEMA_VERSION = '1.4.0';

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
            "CREATE TABLE " . self::tableName('stripe_sessions') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                stripe_event_id varchar(191) NOT NULL,
                event_type varchar(191) NOT NULL DEFAULT '',
                processed_at datetime DEFAULT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY stripe_event_id (stripe_event_id),
                KEY event_type (event_type),
                KEY processed_at (processed_at)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('user_favorites') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                post_id bigint(20) unsigned NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_post (user_id, post_id),
                KEY user_id (user_id),
                KEY post_id (post_id),
                KEY created_at (created_at)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('user_history') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                post_id bigint(20) unsigned NOT NULL,
                viewed_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY post_id (post_id),
                KEY viewed_at (viewed_at)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('user_subscriptions') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                topic_slug varchar(191) NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_topic (user_id, topic_slug),
                KEY user_id (user_id),
                KEY topic_slug (topic_slug)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('specialist_profiles') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                bio text,
                specializations text,
                socials text,
                status varchar(50) NOT NULL DEFAULT 'pending',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY user_id (user_id),
                KEY status (status)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('specialist_earnings') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                amount decimal(12,2) NOT NULL DEFAULT 0.00,
                currency varchar(10) NOT NULL DEFAULT 'PLN',
                source varchar(100) NOT NULL DEFAULT '',
                period varchar(20) NOT NULL DEFAULT '',
                status varchar(50) NOT NULL DEFAULT 'pending',
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY status (status),
                KEY period (period)
            ) {$charsetCollate};",
            "CREATE TABLE " . self::tableName('platform_notifications') . " (
                id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                user_id bigint(20) unsigned NOT NULL,
                type varchar(100) NOT NULL DEFAULT '',
                title varchar(255) NOT NULL DEFAULT '',
                body text,
                read_at datetime DEFAULT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY  (id),
                KEY user_id (user_id),
                KEY type (type),
                KEY read_at (read_at),
                KEY created_at (created_at)
            ) {$charsetCollate};",
        ];
    }
}
