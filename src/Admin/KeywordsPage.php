<?php

namespace PearTree\ProgrammaticAffiliate\Admin;

use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;

class KeywordsPage
{
    private AffiliateRepository $repository;

    public function __construct(AffiliateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->handleActions();

        $products = $this->repository->getProducts();
        $keywords = $this->repository->getKeywords();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Słowa kluczowe autolink', 'peartree-pro-programmatic-affiliate'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ppae_save_keyword'); ?>
                <input type="hidden" name="ppae_action" value="save_keyword">
                <table class="form-table">
                    <tr><th>Słowo kluczowe</th><td><input class="regular-text" type="text" name="keyword" required></td></tr>
                    <tr><th>Produkt</th><td><select name="product_id" required><option value="">Wybierz produkt</option><?php foreach ($products as $product) : ?><option value="<?php echo esc_attr((string) (int) ($product['id'] ?? 0)); ?>"><?php echo esc_html((string) ($product['title'] ?? '')); ?></option><?php endforeach; ?></select></td></tr>
                </table>
                <?php submit_button(__('Dodaj słowo kluczowe', 'peartree-pro-programmatic-affiliate')); ?>
            </form>

            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Słowo kluczowe</th><th>Produkt</th><th>Akcje</th></tr></thead>
                <tbody>
                <?php if (empty($keywords)) : ?>
                    <tr><td colspan="4">Brak słów kluczowych.</td></tr>
                <?php else : foreach ($keywords as $keyword) :
                    $deleteUrl = wp_nonce_url(add_query_arg(['page' => 'ppae-keywords', 'delete' => (int) ($keyword['id'] ?? 0)], admin_url('admin.php')), 'ppae_delete_keyword');
                    ?>
                    <tr>
                        <td><?php echo esc_html((string) (int) ($keyword['id'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($keyword['keyword'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($keyword['product_title'] ?? '')); ?></td>
                        <td><a class="button button-small" href="<?php echo esc_url($deleteUrl); ?>" onclick="return confirm('Usunąć słowo kluczowe?');">Usuń</a></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function handleActions(): void
    {
        if (isset($_POST['ppae_action']) && (string) wp_unslash($_POST['ppae_action']) === 'save_keyword') {
            check_admin_referer('ppae_save_keyword');
            $this->repository->upsertKeyword([
                'keyword' => isset($_POST['keyword']) ? wp_unslash($_POST['keyword']) : '',
                'product_id' => isset($_POST['product_id']) ? (int) wp_unslash($_POST['product_id']) : 0,
            ]);
        }

        if (isset($_GET['delete'])) {
            $nonce = isset($_GET['_wpnonce']) ? (string) wp_unslash($_GET['_wpnonce']) : '';
            if (wp_verify_nonce($nonce, 'ppae_delete_keyword')) {
                $this->repository->deleteKeyword((int) wp_unslash($_GET['delete']));
            }
        }
    }
}

