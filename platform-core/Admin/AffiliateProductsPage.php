<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Affiliate\ProductRepository;

if (! defined('ABSPATH')) {
    exit;
}

final class AffiliateProductsPage
{
    private const PAGE_SLUG = 'poradnik-affiliate-products';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
        add_action('admin_post_poradnik_affiliate_save_product', [self::class, 'handleSave']);
        add_action('admin_post_poradnik_affiliate_delete_product', [self::class, 'handleDelete']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik Affiliate Products', 'poradnik-platform'),
            __('Affiliate Products', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function handleSave(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have permission to manage affiliate products.', 'poradnik-platform'));
        }

        check_admin_referer('poradnik_affiliate_save_product');

        $productId = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
        $data = [
            'name' => isset($_POST['name']) ? wp_unslash($_POST['name']) : '',
            'slug' => isset($_POST['slug']) ? wp_unslash($_POST['slug']) : '',
            'affiliate_url' => isset($_POST['affiliate_url']) ? wp_unslash($_POST['affiliate_url']) : '',
            'category_id' => isset($_POST['category_id']) ? absint($_POST['category_id']) : 0,
            'status' => isset($_POST['status']) ? wp_unslash($_POST['status']) : 'draft',
        ];

        $savedId = ProductRepository::save($data, $productId);

        if ($savedId < 1) {
            $redirect = add_query_arg(
                [
                    'page' => self::PAGE_SLUG,
                    'error' => '1',
                    'product_id' => $productId,
                ],
                admin_url('tools.php')
            );

            wp_safe_redirect($redirect);
            exit;
        }

        $redirect = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                'updated' => '1',
                'product_id' => $savedId,
            ],
            admin_url('tools.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public static function handleDelete(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have permission to delete affiliate products.', 'poradnik-platform'));
        }

        check_admin_referer('poradnik_affiliate_delete_product');

        $productId = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
        ProductRepository::delete($productId);

        $redirect = add_query_arg(
            [
                'page' => self::PAGE_SLUG,
                'deleted' => '1',
            ],
            admin_url('tools.php')
        );

        wp_safe_redirect($redirect);
        exit;
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $editingId = isset($_GET['product_id']) ? absint($_GET['product_id']) : 0;
        $editingProduct = $editingId > 0 ? ProductRepository::findById($editingId) : null;
        $products = ProductRepository::findAll();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Affiliate Products', 'poradnik-platform') . '</h1>';

        if (isset($_GET['updated']) && $_GET['updated'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Affiliate product saved.', 'poradnik-platform') . '</p></div>';
        }

        if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Affiliate product deleted.', 'poradnik-platform') . '</p></div>';
        }

        if (isset($_GET['error']) && $_GET['error'] === '1') {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Affiliate product could not be saved.', 'poradnik-platform') . '</p></div>';
        }

        self::renderForm($editingProduct);
        self::renderTable($products);

        echo '</div>';
    }

    /**
     * @param array<string, mixed>|null $product
     */
    private static function renderForm(?array $product): void
    {
        $productId = is_array($product) && isset($product['id']) ? absint($product['id']) : 0;
        $name = is_array($product) && isset($product['name']) ? (string) $product['name'] : '';
        $slug = is_array($product) && isset($product['slug']) ? (string) $product['slug'] : '';
        $affiliateUrl = is_array($product) && isset($product['affiliate_url']) ? (string) $product['affiliate_url'] : '';
        $status = is_array($product) && isset($product['status']) ? (string) $product['status'] : 'draft';

        echo '<h2>' . esc_html($productId > 0 ? 'Edit Product' : 'Add Product') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="max-width: 720px; margin-bottom: 24px;">';
        wp_nonce_field('poradnik_affiliate_save_product');
        echo '<input type="hidden" name="action" value="poradnik_affiliate_save_product" />';
        echo '<input type="hidden" name="product_id" value="' . esc_attr((string) $productId) . '" />';

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="poradnik-affiliate-name">Name</label></th><td><input id="poradnik-affiliate-name" name="name" type="text" class="regular-text" value="' . esc_attr($name) . '" required /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-affiliate-slug">Slug</label></th><td><input id="poradnik-affiliate-slug" name="slug" type="text" class="regular-text" value="' . esc_attr($slug) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-affiliate-url">Affiliate URL</label></th><td><input id="poradnik-affiliate-url" name="affiliate_url" type="url" class="large-text" value="' . esc_attr($affiliateUrl) . '" required /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-affiliate-status">Status</label></th><td><select id="poradnik-affiliate-status" name="status"><option value="publish" ' . selected($status, 'publish', false) . '>publish</option><option value="draft" ' . selected($status, 'draft', false) . '>draft</option></select></td></tr>';
        echo '</table>';

        submit_button($productId > 0 ? __('Update Product', 'poradnik-platform') : __('Add Product', 'poradnik-platform'));
        echo '</form>';
    }

    /**
     * @param array<int, array<string, mixed>> $products
     */
    private static function renderTable(array $products): void
    {
        echo '<h2>' . esc_html__('Product List', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width: 960px;">';
        echo '<thead><tr><th>ID</th><th>Name</th><th>Slug</th><th>Status</th><th>Actions</th></tr></thead><tbody>';

        if ($products === []) {
            echo '<tr><td colspan="5">' . esc_html__('No affiliate products found.', 'poradnik-platform') . '</td></tr>';
        }

        foreach ($products as $product) {
            $productId = isset($product['id']) ? absint($product['id']) : 0;
            $editUrl = add_query_arg(
                [
                    'page' => self::PAGE_SLUG,
                    'product_id' => $productId,
                ],
                admin_url('tools.php')
            );
            $deleteUrl = wp_nonce_url(
                add_query_arg(
                    [
                        'action' => 'poradnik_affiliate_delete_product',
                        'product_id' => $productId,
                    ],
                    admin_url('admin-post.php')
                ),
                'poradnik_affiliate_delete_product'
            );

            echo '<tr>';
            echo '<td>' . esc_html((string) $productId) . '</td>';
            echo '<td>' . esc_html((string) ($product['name'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($product['slug'] ?? '')) . '</td>';
            echo '<td>' . esc_html((string) ($product['status'] ?? 'draft')) . '</td>';
            echo '<td><a href="' . esc_url($editUrl) . '">Edit</a> | <a href="' . esc_url($deleteUrl) . '" onclick="return confirm(\'Delete this affiliate product?\');">Delete</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
