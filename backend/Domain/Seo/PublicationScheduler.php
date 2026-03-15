<?php

namespace Poradnik\Platform\Domain\Seo;

use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * WP-Cron job for scheduled publication of programmatic SEO drafts.
 *
 * Admin settings (Options):
 *   poradnik_programmatic_daily_limit  (int, default 5)
 *   poradnik_programmatic_auto_publish (bool, default false)
 *
 * Cron schedule: twicedaily
 * Hook: poradnik_programmatic_publish_batch
 *
 * Flow:
 *  1. Fetch up to $dailyLimit drafts with _poradnik_programmatic_template meta.
 *  2. Run QaGuardrails on each.
 *  3. Passed → publish (if auto-publish enabled) or add to review queue.
 *  4. Failed → mark _poradnik_qa_failed meta for editorial fix.
 */
final class PublicationScheduler
{
    public const CRON_HOOK     = 'poradnik_programmatic_publish_batch';
    public const CRON_SCHEDULE = 'twicedaily';

    public static function init(): void
    {
        add_action(self::CRON_HOOK, [self::class, 'run']);

        if (! wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    public static function run(): void
    {
        $autoPublish = (bool) get_option('poradnik_programmatic_auto_publish', false);
        $dailyLimit  = max(1, (int) get_option('poradnik_programmatic_daily_limit', 5));
        $todayCount  = self::countPublishedToday();

        if ($todayCount >= $dailyLimit) {
            EventLogger::dispatch('poradnik_programmatic_daily_limit_reached', [
                'published_today' => $todayCount,
                'limit'           => $dailyLimit,
            ]);
            return;
        }

        $remaining = $dailyLimit - $todayCount;

        /** @var \WP_Post[] $drafts */
        $drafts = get_posts([
            'post_type'      => ['guide', 'ranking', 'comparison', 'news', 'tool'],
            'post_status'    => 'draft',
            'posts_per_page' => $remaining,
            'order'          => 'ASC',
            'orderby'        => 'date',
            'meta_query'     => [
                [
                    'key'     => '_poradnik_programmatic_template',
                    'compare' => 'EXISTS',
                ],
                [
                    'key'     => '_poradnik_qa_failed',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ]);

        if (empty($drafts)) {
            return;
        }

        $published = 0;
        $failed    = 0;

        foreach ($drafts as $post) {
            $qaResult = QaGuardrails::check($post->ID);

            if (is_wp_error($qaResult)) {
                update_post_meta($post->ID, '_poradnik_qa_failed', $qaResult->get_error_message());
                update_post_meta($post->ID, '_poradnik_qa_failed_at', current_time('mysql', true));

                EventLogger::dispatch('poradnik_programmatic_qa_failed', [
                    'post_id' => $post->ID,
                    'issues'  => $qaResult->get_error_data()['issues'] ?? [],
                ]);

                $failed++;
                continue;
            }

            if ($autoPublish) {
                wp_update_post(['ID' => $post->ID, 'post_status' => 'publish']);

                update_post_meta($post->ID, '_poradnik_published_at', current_time('mysql', true));
                delete_post_meta($post->ID, '_poradnik_qa_failed');

                EventLogger::dispatch('poradnik_programmatic_published', ['post_id' => $post->ID]);
                $published++;
            } else {
                // Move to 'pending' for editorial review.
                wp_update_post(['ID' => $post->ID, 'post_status' => 'pending']);

                EventLogger::dispatch('poradnik_programmatic_pending_review', ['post_id' => $post->ID]);
                $published++;
            }
        }

        EventLogger::dispatch('poradnik_programmatic_batch_completed', [
            'published' => $published,
            'failed'    => $failed,
            'remaining' => $remaining - $published,
        ]);
    }

    private static function countPublishedToday(): int
    {
        global $wpdb;

        $today = gmdate('Y-m-d');

        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p
                 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
                 WHERE p.post_status = 'publish'
                   AND pm.meta_key = '_poradnik_published_at'
                   AND DATE(pm.meta_value) = %s",
                $today
            )
        );
    }
}
