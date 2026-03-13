<?php

namespace Poradnik\Platform\Domain\Security;

if (! defined('ABSPATH')) {
    exit;
}

final class SecurityAuditLogger
{
    private const OPTION_KEY = 'poradnik_security_audit_log';
    private const MAX_ENTRIES = 500;

    public static function log(string $event, string $description, int $userId = 0): void
    {
        $entries = self::getEntries();

        $entry = [
            'time' => current_time('mysql', true),
            'event' => sanitize_key($event),
            'description' => sanitize_text_field($description),
            'user_id' => $userId > 0 ? $userId : get_current_user_id(),
            'ip' => self::clientIp(),
        ];

        array_unshift($entries, $entry);

        if (count($entries) > self::MAX_ENTRIES) {
            $entries = array_slice($entries, 0, self::MAX_ENTRIES);
        }

        update_option(self::OPTION_KEY, $entries, false);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function getEntries(int $limit = 100): array
    {
        $entries = get_option(self::OPTION_KEY, []);

        if (! is_array($entries)) {
            return [];
        }

        return array_slice($entries, 0, $limit);
    }

    public static function clearLog(): void
    {
        delete_option(self::OPTION_KEY);
    }

    private static function clientIp(): string
    {
        $ip = '';

        if (! empty($_SERVER['REMOTE_ADDR'])) {
            $ip = (string) $_SERVER['REMOTE_ADDR'];
        }

        return sanitize_text_field($ip);
    }
}
