-- Poradnik.pro Platform Schema v1.4.0
-- Reference SQL for WordPress dbDelta migrations (managed by backend/Infrastructure/Database/Migrator.php)
-- All tables use the WordPress table prefix (default: wp_).
-- Character set and collation are set dynamically by WordPress.

CREATE TABLE `{prefix}poradnik_affiliate_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `affiliate_url` text NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  KEY `status` (`status`)
);

CREATE TABLE `{prefix}poradnik_affiliate_clicks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint(20) unsigned NOT NULL,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `source` varchar(191) DEFAULT '',
  `referrer` varchar(255) DEFAULT '',
  `user_ip` varchar(45) DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `post_id` (`post_id`),
  KEY `source` (`source`),
  KEY `created_at` (`created_at`)
);

CREATE TABLE `{prefix}poradnik_affiliate_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `slug` varchar(191) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slug` (`slug`)
);

CREATE TABLE `{prefix}poradnik_ad_campaigns` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) NOT NULL,
  `advertiser_id` bigint(20) unsigned DEFAULT NULL,
  `slot_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'draft',
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `budget` decimal(12,2) DEFAULT 0.00,
  `destination_url` text DEFAULT NULL,
  `creative_text` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `advertiser_id` (`advertiser_id`),
  KEY `slot_id` (`slot_id`),
  KEY `status` (`status`),
  KEY `start_date` (`start_date`),
  KEY `end_date` (`end_date`)
);

CREATE TABLE `{prefix}poradnik_ad_slots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `slot_key` varchar(191) NOT NULL,
  `label` varchar(191) NOT NULL,
  `location` varchar(191) DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `slot_key` (`slot_key`),
  KEY `location` (`location`)
);

CREATE TABLE `{prefix}poradnik_ad_clicks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `slot_id` bigint(20) unsigned DEFAULT NULL,
  `source` varchar(191) DEFAULT '',
  `user_ip` varchar(45) DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `slot_id` (`slot_id`),
  KEY `source` (`source`),
  KEY `created_at` (`created_at`)
);

CREATE TABLE `{prefix}poradnik_ad_impressions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `campaign_id` bigint(20) unsigned NOT NULL,
  `slot_id` bigint(20) unsigned DEFAULT NULL,
  `source` varchar(191) DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `campaign_id` (`campaign_id`),
  KEY `slot_id` (`slot_id`),
  KEY `source` (`source`),
  KEY `created_at` (`created_at`)
);

CREATE TABLE `{prefix}poradnik_sponsored_articles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned DEFAULT NULL,
  `advertiser_id` bigint(20) unsigned DEFAULT NULL,
  `advertiser_email` varchar(191) DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` longtext,
  `package_key` varchar(100) NOT NULL DEFAULT 'basic',
  `status` varchar(50) NOT NULL DEFAULT 'pending',
  `payment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `amount` decimal(12,2) DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'PLN',
  `stripe_payment_intent` varchar(191) DEFAULT NULL,
  `desired_publish_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `advertiser_id` (`advertiser_id`),
  KEY `advertiser_email` (`advertiser_email`),
  KEY `package_key` (`package_key`),
  KEY `status` (`status`),
  KEY `payment_status` (`payment_status`)
);

CREATE TABLE `{prefix}poradnik_stripe_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stripe_event_id` varchar(191) NOT NULL,
  `event_type` varchar(191) NOT NULL DEFAULT '',
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `stripe_event_id` (`stripe_event_id`),
  KEY `event_type` (`event_type`),
  KEY `processed_at` (`processed_at`)
);

CREATE TABLE `{prefix}poradnik_image_generation_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `force_regenerate` tinyint(1) NOT NULL DEFAULT 0,
  `last_error` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `status` (`status`),
  KEY `created_at` (`created_at`)
);
