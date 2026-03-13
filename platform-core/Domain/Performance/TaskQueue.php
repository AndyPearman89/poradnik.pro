<?php

namespace Poradnik\Platform\Domain\Performance;

if (! defined('ABSPATH')) {
    exit;
}

final class TaskQueue
{
    private const HOOK_PREFIX = 'poradnik_task_queue_';

    /**
     * Schedule a heavy task via Action Scheduler / WP-Cron fallback.
     *
     * @param string $taskName  Unique task identifier (e.g. "ai_generate", "programmatic_build")
     * @param array<string, mixed> $args
     * @param int $delaySeconds How far in the future to schedule (default: immediately / 0)
     */
    public static function enqueue(string $taskName, array $args = [], int $delaySeconds = 0): void
    {
        $taskName = sanitize_key($taskName);
        $hook = self::HOOK_PREFIX . $taskName;
        $timestamp = time() + max(0, $delaySeconds);

        if (function_exists('as_schedule_single_action')) {
            as_schedule_single_action($timestamp, $hook, [$args], 'poradnik-platform');
            return;
        }

        if (! wp_next_scheduled($hook)) {
            wp_schedule_single_event($timestamp, $hook, [$args]);
        }
    }

    /**
     * Register a handler for a task.
     *
     * @param string $taskName
     * @param callable $handler  Receives ($args): void
     */
    public static function register(string $taskName, callable $handler): void
    {
        $taskName = sanitize_key($taskName);
        $hook = self::HOOK_PREFIX . $taskName;

        add_action($hook, $handler, 10, 1);
    }

    /**
     * Check if a task is already scheduled (WP-Cron fallback only).
     */
    public static function isScheduled(string $taskName): bool
    {
        $taskName = sanitize_key($taskName);
        $hook = self::HOOK_PREFIX . $taskName;

        return (bool) wp_next_scheduled($hook);
    }
}
