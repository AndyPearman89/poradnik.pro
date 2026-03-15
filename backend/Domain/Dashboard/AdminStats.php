<?php

namespace Poradnik\Platform\Domain\Dashboard;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class AdminStats
{
    /**
     * @return array<string, mixed>
     */
    public static function overview(): array
    {
        return [
            'users_total'       => self::countUsers(),
            'posts_total'       => self::countPosts('post'),
            'guides_total'      => self::countPosts('guide'),
            'rankings_total'    => self::countPosts('ranking'),
            'reviews_total'     => self::countPosts('review'),
            'ads_active'        => self::countActiveCampaigns(),
            'sponsored_pending' => self::countSponsoredByStatus('pending'),
            'comments_pending'  => self::countCommentsByStatus('hold'),
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function usersByRole(): array
    {
        $counts = count_users();
        $result = [];

        foreach ($counts['avail_roles'] as $role => $count) {
            if ($count > 0) {
                $result[$role] = $count;
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function recentContent(int $limit = 10): array
    {
        $args = [
            'post_type'      => ['post', 'guide', 'ranking', 'review'],
            'post_status'    => ['publish', 'pending', 'draft'],
            'posts_per_page' => max(1, min(50, $limit)),
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query = new \WP_Query($args);
        $items = [];

        foreach ($query->posts as $post) {
            if (! $post instanceof \WP_Post) {
                continue;
            }

            $items[] = [
                'id'     => $post->ID,
                'title'  => $post->post_title,
                'type'   => $post->post_type,
                'status' => $post->post_status,
                'date'   => $post->post_date,
                'author' => get_the_author_meta('display_name', (int) $post->post_author),
            ];
        }

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    public static function trafficSummary(): array
    {
        global $wpdb;

        $clicksTable = Migrator::tableName('ad_clicks');
        $impressionsTable = Migrator::tableName('ad_impressions');
        $affiliateTable = Migrator::tableName('affiliate_clicks');

        $adClicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$clicksTable}");
        $adImpressions = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$impressionsTable}");
        $affiliateClicks = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$affiliateTable}");

        $ctr = $adImpressions > 0 ? round(($adClicks / $adImpressions) * 100, 2) : 0.0;

        return [
            'ad_impressions'   => $adImpressions,
            'ad_clicks'        => $adClicks,
            'ad_ctr'           => $ctr,
            'affiliate_clicks' => $affiliateClicks,
        ];
    }

    private static function countUsers(): int
    {
        $counts = count_users();

        return (int) ($counts['total_users'] ?? 0);
    }

    private static function countPosts(string $postType): int
    {
        $counts = wp_count_posts($postType);
        if (! is_object($counts)) {
            return 0;
        }

        $publish = isset($counts->publish) ? (int) $counts->publish : 0;
        $pending = isset($counts->pending) ? (int) $counts->pending : 0;

        return $publish + $pending;
    }

    private static function countActiveCampaigns(): int
    {
        global $wpdb;

        $table = Migrator::tableName('ad_campaigns');
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s",
            'active'
        ));

        return (int) $count;
    }

    private static function countSponsoredByStatus(string $status): int
    {
        global $wpdb;

        $table = Migrator::tableName('sponsored_articles');
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE status = %s",
            $status
        ));

        return (int) $count;
    }

    private static function countCommentsByStatus(string $status): int
    {
        $counts = wp_count_comments();
        if (! is_object($counts)) {
            return 0;
        }

        $value = $counts->{$status} ?? 0;

        return (int) $value;
    }
}
