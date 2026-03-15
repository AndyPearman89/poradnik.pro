<?php

namespace Poradnik\Platform\Domain\Seo;

use Poradnik\Platform\Domain\Affiliate\ProductRepository;

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

        $howToSchema = self::howToSchema($post);
        if ($howToSchema !== null) {
            $data[] = $howToSchema;
        }

        if (in_array($post->post_type, ['review'], true)) {
            $data[] = self::reviewSchema($post);
        }

        if (in_array($post->post_type, ['ranking'], true)) {
            $data[] = self::rankingSchema($post);
        }

        if (in_array($post->post_type, ['comparison'], true)) {
            $data[] = self::comparisonSchema($post);
        }

        if (in_array($post->post_type, ['tool'], true)) {
            $data[] = self::toolSchema($post);
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
        $schema = [
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

        $clusterTopic = trim((string) get_post_meta($post->ID, '_poradnik_cluster_topic', true));
        if ($clusterTopic !== '') {
            $schema['about'] = [
                '@type' => 'Thing',
                'name' => $clusterTopic,
            ];
        }

        $mentions = self::relatedMentions($post->ID);
        if ($mentions !== []) {
            $schema['mentions'] = $mentions;
        }

        return $schema;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function relatedMentions(int $postId): array
    {
        $rawRelated = get_post_meta($postId, 'related_articles', true);
        $ids = is_array($rawRelated) ? array_map('absint', $rawRelated) : [];

        if ($ids === []) {
            $rawClusterIds = get_post_meta($postId, '_poradnik_cluster_post_ids', true);
            if (is_string($rawClusterIds)) {
                $decoded = json_decode($rawClusterIds, true);
                if (is_array($decoded)) {
                    $ids = array_map('absint', array_values($decoded));
                }
            } elseif (is_array($rawClusterIds)) {
                $ids = array_map('absint', array_values($rawClusterIds));
            }
        }

        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0 && $id !== $postId));
        if ($ids === []) {
            return [];
        }

        $mentions = [];
        foreach ($ids as $id) {
            $url = get_permalink($id);
            if (! is_string($url) || $url === '') {
                continue;
            }

            $title = get_the_title($id);
            $mentions[] = [
                '@type' => 'WebPage',
                'name' => is_string($title) ? $title : '',
                'url' => $url,
            ];
        }

        return $mentions;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function faqSchema(int $postId): ?array
    {
        $faq = get_post_meta($postId, 'faq_items', true);

        $entities = [];

        if (is_array($faq) && $faq !== []) {
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
        }

        if ($entities === []) {
            $entities = self::autoFaqEntitiesFromContent($postId);
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
     * @return array<int, array<string, mixed>>
     */
    private static function autoFaqEntitiesFromContent(int $postId): array
    {
        $content = (string) get_post_field('post_content', $postId);
        if ($content === '') {
            return [];
        }

        preg_match_all('/<h2[^>]*>(.*?)<\/h2>\s*(?:<p[^>]*>(.*?)<\/p>)?/is', $content, $matches, PREG_SET_ORDER);
        if (! is_array($matches) || $matches === []) {
            return [];
        }

        $entities = [];
        foreach ($matches as $index => $match) {
            if ($index >= 3) {
                break;
            }

            $heading = trim(wp_strip_all_tags((string) ($match[1] ?? '')));
            if ($heading === '') {
                continue;
            }

            $question = $heading;
            if (! preg_match('/\?$/u', $question)) {
                $question = 'Jak ' . mb_strtolower(rtrim($question, '.:;!')) . '?';
            }

            $answer = trim(wp_strip_all_tags((string) ($match[2] ?? '')));
            if ($answer === '') {
                $answer = wp_trim_words(wp_strip_all_tags($content), 22);
            }

            if ($answer === '') {
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

        return $entities;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function howToSchema(\WP_Post $post): ?array
    {
        if ($post->post_type !== 'guide') {
            return null;
        }

        $steps = self::guideSteps($post->ID);
        if ($steps === []) {
            $steps = self::guideStepsFromContent($post->ID);
        }

        if ($steps === []) {
            return null;
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => get_the_title($post),
            'url' => get_permalink($post),
            'step' => $steps,
        ];

        $description = MetaService::metaDescription();
        if ($description !== '') {
            $schema['description'] = $description;
        }

        $imageUrl = get_the_post_thumbnail_url($post, 'full');
        if (is_string($imageUrl) && $imageUrl !== '') {
            $schema['image'] = $imageUrl;
        }

        $estimatedTime = self::guideEstimatedTime($post->ID);
        if ($estimatedTime > 0) {
            $schema['totalTime'] = 'PT' . $estimatedTime . 'M';
        }

        $tools = self::guideTools($post->ID);
        if ($tools !== []) {
            $schema['tool'] = array_map(
                static fn (string $tool): array => [
                    '@type' => 'HowToTool',
                    'name' => $tool,
                ],
                $tools
            );
        }

        return $schema;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function guideSteps(int $postId): array
    {
        $raw = self::acfOrMeta($postId, 'timeline_steps');
        if (! is_array($raw) || $raw === []) {
            $raw = self::acfOrMeta($postId, 'steps');
        }
        if (! is_array($raw) || $raw === []) {
            $raw = get_post_meta($postId, 'guide_steps', true);
        }

        if (! is_array($raw) || $raw === []) {
            return [];
        }

        $steps = [];
        foreach ($raw as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $name = trim(wp_strip_all_tags((string) ($row['step_title'] ?? $row['title'] ?? '')));
            $text = trim(wp_strip_all_tags((string) ($row['step_description'] ?? $row['description'] ?? $row['content'] ?? '')));
            if ($name === '' && $text === '') {
                continue;
            }

            $position = absint($row['step_order'] ?? $row['order'] ?? ($index + 1));
            $step = [
                '@type' => 'HowToStep',
                'position' => $position > 0 ? $position : ($index + 1),
                'name' => $name,
                'text' => $text,
            ];

            $imageId = absint($row['step_image'] ?? $row['image_id'] ?? $row['image'] ?? 0);
            if ($imageId > 0) {
                $imageUrl = wp_get_attachment_url($imageId);
                if (is_string($imageUrl) && $imageUrl !== '') {
                    $step['image'] = $imageUrl;
                }
            }

            $steps[] = $step;
        }

        usort(
            $steps,
            static function (array $left, array $right): int {
                return ((int) ($left['position'] ?? 0)) <=> ((int) ($right['position'] ?? 0));
            }
        );

        return $steps;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function guideStepsFromContent(int $postId): array
    {
        $content = (string) get_post_field('post_content', $postId);
        if ($content === '') {
            return [];
        }

        preg_match_all('/<h2[^>]*>(.*?)<\/h2>\s*(?:<p[^>]*>(.*?)<\/p>)?/is', $content, $matches, PREG_SET_ORDER);
        if (! is_array($matches) || $matches === []) {
            return [];
        }

        $steps = [];
        foreach ($matches as $index => $match) {
            $name = trim(wp_strip_all_tags((string) ($match[1] ?? '')));
            $text = trim(wp_strip_all_tags((string) ($match[2] ?? '')));
            if ($name === '' && $text === '') {
                continue;
            }

            $steps[] = [
                '@type' => 'HowToStep',
                'position' => $index + 1,
                'name' => $name,
                'text' => $text,
            ];

            if (count($steps) >= 12) {
                break;
            }
        }

        return $steps;
    }

    /**
     * @return array<int, string>
     */
    private static function guideTools(int $postId): array
    {
        $raw = self::acfOrMeta($postId, 'tools_needed');
        if (! is_string($raw) || trim($raw) === '') {
            $raw = get_post_meta($postId, 'guide_tools', true);
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $parts = preg_split('/\r\n|\r|\n|,/', $raw) ?: [];
        $tools = [];
        foreach ($parts as $part) {
            $tool = trim(wp_strip_all_tags((string) $part));
            if ($tool !== '') {
                $tools[] = $tool;
            }
        }

        return $tools;
    }

    private static function guideEstimatedTime(int $postId): int
    {
        return absint((string) self::acfOrMeta($postId, 'estimated_time'));
    }

    private static function acfOrMeta(int $postId, string $fieldName)
    {
        if (function_exists('get_field')) {
            $value = get_field($fieldName, $postId);
            if ($value !== null && $value !== false && $value !== '') {
                return $value;
            }
        }

        return get_post_meta($postId, $fieldName, true);
    }

    /**
     * @return array<string, mixed>
     */
    private static function reviewSchema(\WP_Post $post): array
    {
        $rating = (float) self::acfOrMeta($post->ID, 'rating');
        if ($rating <= 0) {
            $rating = (float) get_post_meta($post->ID, 'review_rating', true);
        }

        $verdict = trim(wp_strip_all_tags((string) self::acfOrMeta($post->ID, 'verdict')));
        if ($verdict === '') {
            $verdict = trim(wp_strip_all_tags((string) get_post_meta($post->ID, 'review_verdict', true)));
        }

        $affiliateLink = esc_url_raw((string) self::acfOrMeta($post->ID, 'affiliate_link'));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => [
                '@type' => 'Product',
                'name' => get_the_title($post),
            ],
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => (string) max(1, min(5, $rating > 0 ? $rating : 4.5)),
                'bestRating' => '5',
                'worstRating' => '1',
            ],
            'author' => [
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', (int) $post->post_author),
            ],
        ];

        if ($verdict !== '') {
            $schema['reviewBody'] = $verdict;
        }

        if ($affiliateLink !== '') {
            $schema['itemReviewed']['offers'] = [
                '@type' => 'Offer',
                'url' => $affiliateLink,
            ];
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private static function rankingSchema(\WP_Post $post): array
    {
        $items = self::rankingItems($post->ID);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => get_the_title($post),
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'numberOfItems' => count($items) > 0 ? count($items) : 10,
        ];

        if ($items !== []) {
            $schema['itemListElement'] = $items;
        }

        return $schema;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function rankingItems(int $postId): array
    {
        $ids = get_post_meta($postId, 'ranking_product_ids', true);
        if (is_array($ids) && $ids !== [] && class_exists(ProductRepository::class)) {
            $products = ProductRepository::findByIds(array_map('absint', $ids));
            if ($products !== []) {
                return self::rankingListItemsFromProducts($products);
            }
        }

        $raw = self::acfOrMeta($postId, 'ranking_products');
        if (! is_array($raw) || $raw === []) {
            return [];
        }

        return self::rankingListItemsFromProducts($raw);
    }

    /**
     * @param array<int, array<string, mixed>> $products
     * @return array<int, array<string, mixed>>
     */
    private static function rankingListItemsFromProducts(array $products): array
    {
        $items = [];

        foreach ($products as $index => $product) {
            if (! is_array($product)) {
                continue;
            }

            $name = trim(wp_strip_all_tags((string) ($product['product_name'] ?? $product['name'] ?? '')));
            if ($name === '') {
                continue;
            }

            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $name,
            ];

            $url = '';
            if (isset($product['affiliate_link'])) {
                $url = esc_url_raw((string) $product['affiliate_link']);
            } elseif (isset($product['id'])) {
                $slug = isset($product['slug']) ? sanitize_title((string) $product['slug']) : '';
                $target = $slug !== '' ? $slug : (string) absint($product['id']);
                $url = home_url('/go/' . rawurlencode($target) . '/');
            }

            if ($url !== '') {
                $item['url'] = $url;
            }

            $items[] = $item;
        }

        return [
            ...$items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function comparisonSchema(\WP_Post $post): array
    {
        $productA = trim(wp_strip_all_tags((string) self::acfOrMeta($post->ID, 'product_a')));
        $productB = trim(wp_strip_all_tags((string) self::acfOrMeta($post->ID, 'product_b')));
        $winner = trim(wp_strip_all_tags((string) self::acfOrMeta($post->ID, 'winner')));

        if ($productA === '') {
            $productA = 'Opcja A';
        }

        if ($productB === '') {
            $productB = 'Opcja B';
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => get_the_title($post),
            'numberOfItems' => 2,
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => $productA,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => $productB,
                ],
            ],
        ];

        if ($winner !== '') {
            $schema['description'] = $winner;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private static function toolSchema(\WP_Post $post): array
    {
        $website = esc_url_raw((string) self::acfOrMeta($post->ID, 'tool_website'));
        $category = trim(wp_strip_all_tags((string) self::acfOrMeta($post->ID, 'tool_category')));
        $price = trim(wp_strip_all_tags((string) self::acfOrMeta($post->ID, 'tool_price')));
        $affiliateLink = esc_url_raw((string) self::acfOrMeta($post->ID, 'affiliate_link'));

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => get_the_title($post),
        ];

        if ($category !== '') {
            $schema['category'] = $category;
        }

        if ($website !== '') {
            $schema['url'] = $website;
        }

        if ($affiliateLink !== '') {
            $offer = [
                '@type' => 'Offer',
                'url' => $affiliateLink,
            ];

            if ($price !== '') {
                $offer['priceSpecification'] = [
                    '@type' => 'PriceSpecification',
                    'price' => $price,
                ];
            }

            $schema['offers'] = $offer;
        }

        return $schema;
    }
}
