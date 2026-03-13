<?php

namespace Poradnik\Platform\Domain\User;

use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Handles user favorites: saving, retrieving and removing bookmarked posts.
 */
final class FavoritesService
{
    /**
     * Get all favorite post IDs for a user.
     *
     * @return array<int, array{id: int, post_id: int, created_at: string}>
     */
    public static function getForUser(int $userId): array
    {
        global $wpdb;

        if ($userId < 1) {
            return [];
        }

        $table = Migrator::tableName('user_favorites');

        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, post_id, created_at FROM {$table} WHERE user_id = %d ORDER BY created_at DESC",
                $userId
            ),
            ARRAY_A
        );

        if (! is_array($rows)) {
            return [];
        }

        return array_map(static function (array $row): array {
            return [
                'id'         => (int) $row['id'],
                'post_id'    => (int) $row['post_id'],
                'created_at' => (string) $row['created_at'],
            ];
        }, $rows);
    }

    /**
     * Add a post to a user's favorites.
     * Returns the new row ID, or 0 on failure (including duplicate).
     */
    public static function add(int $userId, int $postId): int
    {
        global $wpdb;

        if ($userId < 1 || $postId < 1) {
            return 0;
        }

        $table = Migrator::tableName('user_favorites');

        $inserted = $wpdb->insert(
            $table,
            [
                'user_id'    => $userId,
                'post_id'    => $postId,
                'created_at' => current_time('mysql', true),
            ],
            ['%d', '%d', '%s']
        );

        if ($inserted === false || $inserted < 1) {
            return 0;
        }

        return (int) $wpdb->insert_id;
    }

    /**
     * Remove a specific favorite by its row ID, scoped to user for safety.
     */
    public static function remove(int $userId, int $favoriteId): bool
    {
        global $wpdb;

        if ($userId < 1 || $favoriteId < 1) {
            return false;
        }

        $table = Migrator::tableName('user_favorites');

        $deleted = $wpdb->delete(
            $table,
            [
                'id'      => $favoriteId,
                'user_id' => $userId,
            ],
            ['%d', '%d']
        );

        return $deleted !== false && $deleted > 0;
    }
}
