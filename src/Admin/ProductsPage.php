<?php

namespace PearTree\ProgrammaticAffiliate\Admin;

use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;

class ProductsPage
{
    private AffiliateRepository $repository;

    public function __construct(AffiliateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', static function (): void {
            wp_enqueue_style('ppae-affiliate-css', PPAE_URL . 'assets/css/affiliate.css', [], PPAE_VERSION);
        });
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->handleActions();

        $editId = isset($_GET['edit']) ? (int) wp_unslash($_GET['edit']) : 0;
        $edit = $editId > 0 ? $this->repository->getProductById($editId) : null;
        $row = is_array($edit) ? $edit : [
            'id' => 0,
            'title' => '',
            'slug' => '',
            'image' => '',
            'destination_url' => '',
            'price' => '',
            'rating' => '0',
            'description' => '',
            'button_text' => 'SprawdĹş ofertÄ™',
            'category' => '',
            'features' => '',
        ];

        $products = $this->repository->getProducts();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Produkty afiliacyjne', 'peartree-pro-programmatic-affiliate'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ppae_save_product'); ?>
                <input type="hidden" name="ppae_action" value="save_product">
                <input type="hidden" name="id" value="<?php echo esc_attr((string) (int) ($row['id'] ?? 0)); ?>">
                <table class="form-table">
                    <tr><th>Tytuł</th><td><input class="regular-text" type="text" name="title" value="<?php echo esc_attr((string) ($row['title'] ?? '')); ?>" required></td></tr>
                    <tr><th>Slug</th><td><input class="regular-text" type="text" name="slug" value="<?php echo esc_attr((string) ($row['slug'] ?? '')); ?>" required></td></tr>
                    <tr><th>Obraz</th><td><input class="large-text" type="url" name="image" value="<?php echo esc_attr((string) ($row['image'] ?? '')); ?>"></td></tr>
                    <tr><th>Docelowy URL</th><td><input class="large-text" type="url" name="destination_url" value="<?php echo esc_attr((string) ($row['destination_url'] ?? '')); ?>" required></td></tr>
                    <tr><th>Cena</th><td><input class="regular-text" type="text" name="price" value="<?php echo esc_attr((string) ($row['price'] ?? '')); ?>"></td></tr>
                    <tr><th>Ocena</th><td><input class="small-text" type="number" step="0.1" min="0" max="5" name="rating" value="<?php echo esc_attr((string) ($row['rating'] ?? '0')); ?>"></td></tr>
                    <tr><th>Opis</th><td><textarea class="large-text" rows="3" name="description"><?php echo esc_textarea((string) ($row['description'] ?? '')); ?></textarea></td></tr>
                    <tr><th>Tekst przycisku</th><td><input class="regular-text" type="text" name="button_text" value="<?php echo esc_attr((string) ($row['button_text'] ?? 'SprawdĹş ofertÄ™')); ?>"></td></tr>
                    <tr><th>Kategoria</th><td><input class="regular-text" type="text" name="category" value="<?php echo esc_attr((string) ($row['category'] ?? '')); ?>"></td></tr>
                    <tr><th>Cechy</th><td><textarea class="large-text" rows="2" name="features"><?php echo esc_textarea((string) ($row['features'] ?? '')); ?></textarea></td></tr>
                </table>
                <?php submit_button($editId > 0 ? __('Zaktualizuj produkt', 'peartree-pro-programmatic-affiliate') : __('Dodaj produkt', 'peartree-pro-programmatic-affiliate')); ?>
            </form>

            <hr>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Tytuł</th><th>Slug</th><th>Kategoria</th><th>Kliknięcia</th><th>Akcje</th></tr></thead>
                <tbody>
                <?php if (empty($products)) : ?>
                    <tr><td colspan="6"><?php echo esc_html__('Brak produktów.', 'peartree-pro-programmatic-affiliate'); ?></td></tr>
                <?php else : foreach ($products as $product) :
                    $editUrl = add_query_arg(['page' => 'ppae-products', 'edit' => (int) $product['id']], admin_url('admin.php'));
                    $deleteUrl = wp_nonce_url(add_query_arg(['page' => 'ppae-products', 'delete' => (int) $product['id']], admin_url('admin.php')), 'ppae_delete_product');
                    ?>
                    <tr>
                        <td><?php echo esc_html((string) (int) ($product['id'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($product['title'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($product['slug'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($product['category'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) (int) ($product['clicks'] ?? 0)); ?></td>
                        <td><a class="button button-small" href="<?php echo esc_url($editUrl); ?>">Edytuj</a> <a class="button button-small" href="<?php echo esc_url($deleteUrl); ?>" onclick="return confirm('Usunąć produkt?');">Usuń</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function handleActions(): void
    {
        if (isset($_POST['ppae_action']) && (string) wp_unslash($_POST['ppae_action']) === 'save_product') {
            check_admin_referer('ppae_save_product');
            $id = isset($_POST['id']) ? (int) wp_unslash($_POST['id']) : 0;
            $this->repository->upsertProduct([
                'title' => isset($_POST['title']) ? wp_unslash($_POST['title']) : '',
                'slug' => isset($_POST['slug']) ? wp_unslash($_POST['slug']) : '',
                'image' => isset($_POST['image']) ? wp_unslash($_POST['image']) : '',
                'destination_url' => isset($_POST['destination_url']) ? wp_unslash($_POST['destination_url']) : '',
                'price' => isset($_POST['price']) ? wp_unslash($_POST['price']) : '',
                'rating' => isset($_POST['rating']) ? wp_unslash($_POST['rating']) : '0',
                'description' => isset($_POST['description']) ? wp_unslash($_POST['description']) : '',
                'button_text' => isset($_POST['button_text']) ? wp_unslash($_POST['button_text']) : 'SprawdĹş ofertÄ™',
                'category' => isset($_POST['category']) ? wp_unslash($_POST['category']) : '',
                'features' => isset($_POST['features']) ? wp_unslash($_POST['features']) : '',
            ], $id);
        }

        if (isset($_GET['delete'])) {
            $nonce = isset($_GET['_wpnonce']) ? (string) wp_unslash($_GET['_wpnonce']) : '';
            if (wp_verify_nonce($nonce, 'ppae_delete_product')) {
                $this->repository->deleteProduct((int) wp_unslash($_GET['delete']));
            }
        }
    }
}

