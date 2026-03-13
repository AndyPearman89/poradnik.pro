<?php

namespace Poradnik\Platform\Api\Controllers;

use Poradnik\Platform\Domain\User\FavoritesService;
use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * REST controller for the User Dashboard endpoints.
 *
 * Routes:
 *   GET    /wp-json/poradnik/v1/user/favorites
 *   POST   /wp-json/poradnik/v1/user/favorites
 *   DELETE /wp-json/poradnik/v1/user/favorites/{id}
 */
final class UserController
{
    public static function registerRoutes(): void
    {
        register_rest_route('poradnik/v1', '/user/favorites', [
            [
                'methods'             => 'GET',
                'callback'            => [self::class, 'getFavorites'],
                'permission_callback' => [self::class, 'requireLogin'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [self::class, 'addFavorite'],
                'permission_callback' => [self::class, 'requireLogin'],
                'args'                => [
                    'post_id' => [
                        'required'          => true,
                        'validate_callback' => static fn($v) => is_numeric($v) && (int) $v > 0,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ]);

        register_rest_route('poradnik/v1', '/user/favorites/(?P<id>[\d]+)', [
            'methods'             => 'DELETE',
            'callback'            => [self::class, 'removeFavorite'],
            'permission_callback' => [self::class, 'requireLogin'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'validate_callback' => static fn($v) => is_numeric($v) && (int) $v > 0,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);
    }

    public static function requireLogin(): bool
    {
        return is_user_logged_in();
    }

    public static function getFavorites(WP_REST_Request $request): WP_REST_Response
    {
        $userId = get_current_user_id();
        $favorites = FavoritesService::getForUser($userId);

        return new WP_REST_Response(['items' => $favorites], 200);
    }

    public static function addFavorite(WP_REST_Request $request): WP_REST_Response
    {
        $userId = get_current_user_id();
        $postId = absint($request->get_param('post_id'));

        $newId = FavoritesService::add($userId, $postId);

        if ($newId < 1) {
            return new WP_REST_Response(['saved' => false, 'message' => 'Already saved or invalid post.'], 409);
        }

        return new WP_REST_Response(['saved' => true, 'id' => $newId], 201);
    }

    public static function removeFavorite(WP_REST_Request $request): WP_REST_Response
    {
        $userId     = get_current_user_id();
        $favoriteId = absint($request->get_param('id'));

        $removed = FavoritesService::remove($userId, $favoriteId);

        if (! $removed) {
            return new WP_REST_Response(['removed' => false, 'message' => 'Not found.'], 404);
        }

        return new WP_REST_Response(['removed' => true], 200);
    }
}
