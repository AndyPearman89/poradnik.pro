<?php

namespace Poradnik\Platform\Domain\Seo;

if (! defined('ABSPATH')) {
    exit;
}

final class SchemaService
{
    // BreadcrumbList schema is generated via BreadcrumbService::schema().
    // It is appended to the schema data array in forCurrentPost().

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forCurrentPost(): array
    {
        if (! is_singular()) {
            return [];
        }

        $post = get_queried_object();
        if (! $post instanceof \WP_Post) {
            return [];
        }

        $data = [self::articleSchema($post)];

        $faqSchema = self::faqSchema($post->ID);
        if ($faqSchema !== null) {
            $data[] = $faqSchema;
        }

        if (in_array($post->post_type, ['review'], true)) {
            $data[] = self::reviewSchema($post);
        }

        if (in_array($post->post_type, ['ranking'], true)) {
            $data[] = self::rankingSchema($post);
        }

        $breadcrumbSchema = BreadcrumbService::schema();
        if ($breadcrumbSchema !== []) {
            $data[] = $breadcrumbSchema;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private static function articleSchema(\WP_Post $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title($post),
            'datePublished' => get_the_date(DATE_ATOM, $post),
            'dateModified' => get_the_modified_date(DATE_ATOM, $post),
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', (int) $post->post_author),
            ],
            'mainEntityOfPage' => get_permalink($post),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function faqSchema(int $postId): ?array
    {
        $faq = get_post_meta($postId, 'faq_items', true);

        if (! is_array($faq) || $faq === []) {
            return null;
        }

        $entities = [];
        foreach ($faq as $row) {
            if (! is_array($row)) {
                continue;
            }

            $question = isset($row['question']) ? trim(wp_strip_all_tags((string) $row['question'])) : '';
            $answer = isset($row['answer']) ? trim(wp_strip_all_tags((string) $row['answer'])) : '';

            if ($question === '' || $answer === '') {
                continue;
            }

            $entities[] = [
                '@type' => 'Question',
                'name' => $question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $answer,
                ],
            ];
        }

        if ($entities === []) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function reviewSchema(\WP_Post $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'Product',
                'name' => get_the_title($post),
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => '4.5',
                'bestRating' => '5',
                'worstRating' => '1',
            ],
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', (int) $post->post_author),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function rankingSchema(\WP_Post $post): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => get_the_title($post),
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems' => 10,
        ];
    }
}
