<?php

namespace Poradnik\Platform\Modules\Rankings;

use Poradnik\Platform\Domain\Affiliate\ProductRepository;
use Poradnik\Platform\Domain\Ranking\Builder;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        add_filter('the_content', [self::class, 'injectRankingBlock'], 30);
        add_shortcode('poradnik_ranking', [self::class, 'renderRankingShortcode']);
    }

    public static function injectRankingBlock(string $content): string
    {
        if (! is_singular('ranking')) {
            return $content;
        }

        if (strpos($content, 'poradnik-ranking-builder') !== false) {
            return $content;
        }

        $ranking = self::buildFromPost((int) get_the_ID());
        if ($ranking === '') {
            return $content;
        }

        return $content . $ranking;
    }

    /**
     * @param array<string, string> $atts
     */
    public static function renderRankingShortcode(array $atts = []): string
    {
        $atts = shortcode_atts([
            'post_id' => '0',
        ], $atts, 'poradnik_ranking');

        $postId = absint($atts['post_id']);
        if ($postId < 1) {
            $postId = (int) get_the_ID();
        }

        return self::buildFromPost($postId);
    }

    private static function buildFromPost(int $postId): string
    {
        if ($postId < 1) {
            return '';
        }

        $ids = get_post_meta($postId, 'ranking_product_ids', true);
        if (! is_array($ids) || $ids === []) {
            return '';
        }

        $products = ProductRepository::findByIds(array_map('absint', $ids));
        if ($products === []) {
            return '';
        }

        $items = [];
        foreach ($products as $product) {
            $id = absint($product['id'] ?? 0);
            $items[] = [
                'name' => (string) ($product['name'] ?? 'Produkt'),
                'quality' => (float) ($product['quality'] ?? 8),
                'price' => (float) ($product['price_score'] ?? 8),
                'features' => (float) ($product['features'] ?? 8),
                'support' => (float) ($product['support'] ?? 8),
                'url' => $id > 0 ? home_url('/go/' . rawurlencode((string) ($product['slug'] ?? $id)) . '/') : '#',
            ];
        }

        return Builder::renderTable($items, 'Sprawdz');
    }
}
