<?php

namespace Poradnik\Platform\Modules\AiImageGenerator;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class ImageQueueWorker
{
    private const CRON_HOOK = 'poradnik_ai_image_generation_queue';
    private const CRON_SCHEDULE = 'poradnik_every_minute';
    private const PER_RUN = 10;

    public static function init(): void
    {
        add_filter('cron_schedules', [self::class, 'registerSchedule']);
        add_action(self::CRON_HOOK, [self::class, 'run']);

        if (! wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time() + 60, self::CRON_SCHEDULE, self::CRON_HOOK);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $schedules
     * @return array<string, array<string, mixed>>
     */
    public static function registerSchedule(array $schedules): array
    {
        if (! isset($schedules[self::CRON_SCHEDULE])) {
            $schedules[self::CRON_SCHEDULE] = [
                'interval' => 60,
                'display' => __('Every Minute (Poradnik)', 'poradnik-platform'),
            ];
        }

        return $schedules;
    }

    public static function run(): void
    {
        global $wpdb;

        $table = Migrator::tableName('image_generation_queue');
        $items = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, post_id, attempts, force_regenerate FROM {$table} WHERE status = %s ORDER BY id ASC LIMIT %d",
                'pending',
                self::PER_RUN
            ),
            ARRAY_A
        );

        if (! is_array($items) || $items === []) {
            return;
        }

        foreach ($items as $item) {
            $queueId = isset($item['id']) ? (int) $item['id'] : 0;
            $postId = isset($item['post_id']) ? (int) $item['post_id'] : 0;

            if ($queueId < 1 || $postId < 1) {
                continue;
            }

            $wpdb->update($table, ['status' => 'processing'], ['id' => $queueId], ['%s'], ['%d']);

            $force = isset($item['force_regenerate']) ? (int) $item['force_regenerate'] === 1 : false;
            $result = AiImageGeneratorService::generateForPost($postId, $force);
            $status = ! empty($result['ok']) ? 'done' : 'failed';
            $error = ! empty($result['error']) ? (string) $result['error'] : '';

            $wpdb->update(
                $table,
                [
                    'status' => $status,
                    'attempts' => (int) ($item['attempts'] ?? 0) + 1,
                    'last_error' => $error,
                    'updated_at' => current_time('mysql', true),
                ],
                ['id' => $queueId],
                ['%s', '%d', '%s', '%s'],
                ['%d']
            );
        }
    }
}
