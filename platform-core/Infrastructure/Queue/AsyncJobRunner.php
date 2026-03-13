<?php

namespace Poradnik\Platform\Infrastructure\Queue;

use Poradnik\Platform\Core\EventLogger;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Thin wrapper around wp_schedule_single_event for deferred/async jobs.
 * Keeps heavy operations (AI generation, programmatic SEO batch) off the
 * critical request path.
 */
final class AsyncJobRunner
{
    /**
     * Schedule a one-off background job.
     *
     * @param string        $hook        WP action hook that runs the job.
     * @param array<mixed>  $args        Arguments passed to the hook.
     * @param int           $delaySeconds  Seconds from now to run the job.
     */
    public static function schedule(string $hook, array $args = [], int $delaySeconds = 5): bool
    {
        $timestamp = time() + max(1, $delaySeconds);
        $scheduled = wp_schedule_single_event($timestamp, $hook, $args);

        EventLogger::dispatch(
            'poradnik_async_job_scheduled',
            [
                'hook'  => $hook,
                'delay' => $delaySeconds,
            ]
        );

        return $scheduled !== false;
    }

    /**
     * Run a job immediately in a non-blocking fashion by triggering a loopback
     * request via wp_remote_post (fire-and-forget).
     *
     * Falls back to a direct in-process call if loopback is unavailable.
     *
     * @param callable(): void $callback
     */
    public static function fireAndForget(callable $callback): void
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
            $callback();
            return;
        }

        $callback();
    }
}
