<?php

namespace Poradnik\Platform\Modules\Affiliate;

use Poradnik\Platform\Admin\AffiliateProductsPage;
use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Domain\Affiliate\ClickTracker;
use Poradnik\Platform\Domain\Affiliate\ProductRepository;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    public static function init(): void
    {
        if (is_admin()) {
            AffiliateProductsPage::init();
        }

        add_action('init', [self::class, 'registerRewriteRules'], 9);
        add_action('template_redirect', [self::class, 'handleRedirect']);
        add_filter('query_vars', [self::class, 'registerQueryVars']);

        add_shortcode('affiliate_product', [self::class, 'renderAffiliateProduct']);
        add_shortcode('comparison_table', [self::class, 'renderComparisonTable']);
        add_shortcode('top_product', [self::class, 'renderTopProduct']);
    }

    public static function registerRewriteRules(): void
    {
        add_rewrite_tag('%poradnik_affiliate_slug%', '([^&]+)');
        add_rewrite_rule('^go/([^/]+)/?$', 'index.php?poradnik_affiliate_slug=$matches[1]', 'top');
    }

    /**
     * @param array<int, string> $queryVars
     * @return array<int, string>
     */
    public static function registerQueryVars(array $queryVars): array
    {
        $queryVars[] = 'poradnik_affiliate_slug';

        return $queryVars;
    }

    public static function handleRedirect(): void
    {
        $slug = get_query_var('poradnik_affiliate_slug');
        if (! is_string($slug) || $slug === '') {
            return;
        }

        $product = ctype_digit($slug)
            ? ProductRepository::findById(absint($slug))
            : ProductRepository::findBySlug($slug);

        if (! is_array($product)) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        $productId = isset($product['id']) ? absint($product['id']) : 0;
        $postId = is_singular() ? get_the_ID() : 0;
        $referrer = wp_get_referer();
        $userIp = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';

        $result = ClickTracker::track($productId, $postId, 'redirect', is_string($referrer) ? $referrer : '', $userIp);
        if ($result instanceof WP_Error) {
            EventLogger::dispatch('poradnik_platform_affiliate_redirect_track_failed', ['product_id' => $productId]);
        }

        $targetUrl = isset($product['affiliate_url']) ? esc_url_raw((string) $product['affiliate_url']) : '';
        if ($targetUrl === '') {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            return;
        }

        wp_redirect($targetUrl, 302, 'PoradnikPlatformAffiliate');
        exit;
    }

    /**
     * @param array<string, string> $atts
     */
    public static function renderAffiliateProduct(array $atts = []): string
    {
        $atts = shortcode_atts([
            'id' => '0',
            'label' => 'Sprawdz oferte',
        ], $atts, 'affiliate_product');

        $product = ProductRepository::findById(absint($atts['id']));
        if (! is_array($product)) {
            return '';
        }

        return self::renderProductCard($product, (string) $atts['label'], 'affiliate-product-card');
    }

    /**
     * @param array<string, string> $atts
     */
    public static function renderTopProduct(array $atts = []): string
    {
        $atts = shortcode_atts([
            'id' => '0',
            'label' => 'Top wybor',
        ], $atts, 'top_product');

        $product = ProductRepository::findById(absint($atts['id']));
        if (! is_array($product)) {
            return '';
        }

        return self::renderProductCard($product, (string) $atts['label'], 'affiliate-top-product');
    }

    /**
     * @param array<string, string> $atts
     */
    public static function renderComparisonTable(array $atts = []): string
    {
        $atts = shortcode_atts([
            'ids' => '',
        ], $atts, 'comparison_table');

        $ids = array_map('absint', array_filter(array_map('trim', explode(',', (string) $atts['ids']))));
        $products = ProductRepository::findByIds($ids);
        if ($products === []) {
            return '';
        }

        ob_start();
        echo '<div class="poradnik-affiliate-comparison"><table class="poradnik-affiliate-table"><thead><tr><th>Produkt</th><th>Akcja</th></tr></thead><tbody>';
        foreach ($products as $product) {
            $name = isset($product['name']) ? esc_html((string) $product['name']) : '';
            $url = self::buildRedirectUrl($product);
            echo '<tr>';
            echo '<td>' . $name . '</td>';
            echo '<td><a class="poradnik-affiliate-button" href="' . esc_url($url) . '" rel="nofollow sponsored noopener" target="_blank">Sprawdz</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table></div>';

        return (string) ob_get_clean();
    }

    /**
     * @param array<string, mixed> $product
     */
    private static function renderProductCard(array $product, string $label, string $className): string
    {
        $name = isset($product['name']) ? esc_html((string) $product['name']) : '';
        $url = self::buildRedirectUrl($product);
        $label = esc_html($label);

        return '<div class="' . esc_attr($className) . '"><div class="poradnik-affiliate-name">' . $name . '</div><a class="poradnik-affiliate-button" href="' . esc_url($url) . '" rel="nofollow sponsored noopener" target="_blank">' . $label . '</a></div>';
    }

    /**
     * @param array<string, mixed> $product
     */
    private static function buildRedirectUrl(array $product): string
    {
        $slug = isset($product['slug']) ? sanitize_title((string) $product['slug']) : '';

        if ($slug === '') {
            $productId = isset($product['id']) ? absint($product['id']) : 0;
            return add_query_arg('poradnik_affiliate_slug', (string) $productId, home_url('/'));
        }

        return home_url('/go/' . rawurlencode($slug) . '/');
    }
}
