<?php

namespace Poradnik\Platform\Api\Controllers;

use WP_REST_Request;
use WP_REST_Response;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Search REST controller.
 *
 * Endpoint: GET /wp-json/peartree/search?q={query}
 * Returns:  { questions[], listings[], articles[] }
 *
 * Ref: docs/specs/SEARCH-UI-v1.md
 */
final class SearchController
{
    private const NAMESPACE    = 'peartree';
    private const ROUTE        = '/search';
    private const MAX_RESULTS  = 5;

    public static function registerRoutes(): void
    {
        register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [self::class, 'search'],
            'permission_callback' => '__return_true',
            'args'                => [
                'q' => [
                    'type'              => 'string',
                    'required'          => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'validate_callback' => static function (string $value): bool {
                        return mb_strlen(trim($value), 'UTF-8') >= 2;
                    },
                ],
            ],
        ]);
    }

    public static function search(WP_REST_Request $request): WP_REST_Response
    {
        $query = sanitize_text_field((string) $request->get_param('q'));

        return new WP_REST_Response([
            'query'     => $query,
            'questions' => self::searchPostType($query, 'question', ['_answer_count']),
            'listings'  => self::searchPostType($query, 'listing',  ['_listing_location', '_listing_plan', '_listing_rating']),
            'articles'  => self::searchPostType($query, 'post',     []),
        ], 200);
    }

    /**
     * Search a post type and return normalised result items.
     *
     * @param string   $query     Sanitised search query.
     * @param string   $postType  WordPress post type slug.
     * @param string[] $metaKeys  Meta keys to include in the result.
     * @return array<int, array<string, mixed>>
     */
    private static function searchPostType(string $query, string $postType, array $metaKeys): array
    {
        $wpQuery = new \WP_Query([
            'post_type'      => $postType,
            'post_status'    => 'publish',
            'posts_per_page' => self::MAX_RESULTS,
            's'              => $query,
            'no_found_rows'  => true,
            'fields'         => 'ids',
        ]);

        $results = [];

        foreach ($wpQuery->posts as $postId) {
            $postId = (int) $postId;
            $post   = get_post($postId);

            if (! $post instanceof \WP_Post) {
                continue;
            }

            $item = [
                'id'    => $postId,
                'title' => $post->post_title,
                'url'   => (string) get_permalink($postId),
            ];

            foreach ($metaKeys as $key) {
                $value = get_post_meta($postId, $key, true);
                if ($value !== '' && $value !== false) {
                    $item[ ltrim($key, '_') ] = $value;
                }
            }

            $results[] = $item;
        }

        return $results;
    }
}
