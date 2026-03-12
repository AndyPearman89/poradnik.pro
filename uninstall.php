<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Ustaw true w wp-config.php, aby usuwać tabele przy odinstalowaniu:
 * define('PAA_UNINSTALL_DROP_TABLES', true);
 */
$dropTables = defined('PAA_UNINSTALL_DROP_TABLES') && PAA_UNINSTALL_DROP_TABLES;

/**
 * Czyści dane pluginu w obrębie pojedynczej strony.
 */
$paa_cleanup_site = static function () use ($dropTables): void {
    global $wpdb;

    delete_option('paa_adsense_settings');
    delete_option('paa_db_version');

    delete_transient('paa_affiliate_links_all');
    delete_transient('paa_affiliate_stats');

    $likeTransient = $wpdb->esc_like('_transient_paa_affiliate_slug_') . '%';
    $likeTimeout = $wpdb->esc_like('_transient_timeout_paa_affiliate_slug_') . '%';

    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $likeTransient,
            $likeTimeout
        )
    );

    if ($dropTables) {
        $linksTable = $wpdb->prefix . 'peartree_affiliate_links';
        $clicksTable = $wpdb->prefix . 'peartree_affiliate_clicks';

        $wpdb->query("DROP TABLE IF EXISTS {$linksTable}");
        $wpdb->query("DROP TABLE IF EXISTS {$clicksTable}");
    }
};

if (is_multisite()) {
    $siteIds = get_sites([
        'fields' => 'ids',
        'number' => 0,
    ]);

    if (is_array($siteIds)) {
        $currentBlogId = get_current_blog_id();

        foreach ($siteIds as $siteId) {
            switch_to_blog((int) $siteId);
            $paa_cleanup_site();
        }

        switch_to_blog((int) $currentBlogId);
    }
} else {
    $paa_cleanup_site();
}
