<?php
if (!defined('ABSPATH')) {
    exit;
}

$keyword = (string) ($seoPage['keyword'] ?? '');
$title = (string) ($seoPage['title'] ?? '');
$intro = (string) ($seoPage['content_template'] ?? '');
$ids = array_map(static fn(array $p): int => (int) ($p['id'] ?? 0), $products);
$ids = array_values(array_filter($ids));
$idsCsv = implode(',', $ids);
?>
<article class="ppae-seo-page">
    <header>
        <h1><?php echo esc_html($title); ?></h1>
    </header>

    <section class="ppae-seo-intro">
        <p><?php echo esc_html($intro !== '' ? $intro : ('Szukasz najlepszego rozwiÄ…zania dla: ' . $keyword . '? PoniĹĽej ranking, porĂłwnanie i rekomendacje.')); ?></p>
    </section>

    <?php echo $adsTop; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <section>
        <h2><?php echo esc_html__('Ranking', 'peartree-pro-programmatic-affiliate'); ?></h2>
        <?php echo do_shortcode('[peartree_ranking ids="' . esc_attr($idsCsv) . '"]'); ?>
    </section>

    <section>
        <h2><?php echo esc_html__('Tabela porównawcza', 'peartree-pro-programmatic-affiliate'); ?></h2>
        <?php echo do_shortcode('[peartree_comparison ids="' . esc_attr($idsCsv) . '"]'); ?>
    </section>

    <section>
        <h2><?php echo esc_html__('Rekomendacje afiliacyjne', 'peartree-pro-programmatic-affiliate'); ?></h2>
        <?php foreach (array_slice($products, 0, 3) as $product) : ?>
            <?php echo do_shortcode('[peartree_affiliate_box id="' . (int) ($product['id'] ?? 0) . '"]'); ?>
        <?php endforeach; ?>
    </section>

    <?php echo $adsBottom; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

    <script type="application/ld+json">
        <?php
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $title,
            'itemListElement' => array_map(static function (array $product, int $position): array {
                return [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'item' => [
                        '@type' => 'Product',
                        'name' => (string) ($product['title'] ?? ''),
                        'description' => (string) ($product['description'] ?? ''),
                        'offers' => [
                            '@type' => 'Offer',
                            'price' => (string) ($product['price'] ?? ''),
                        ],
                        'aggregateRating' => [
                            '@type' => 'AggregateRating',
                            'ratingValue' => (string) ($product['rating'] ?? '0'),
                            'reviewCount' => max(1, (int) ($product['clicks'] ?? 1)),
                        ],
                    ],
                ];
            }, $products, range(1, count($products))),
        ];
        echo wp_json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        ?>
        </script>
    </article>

