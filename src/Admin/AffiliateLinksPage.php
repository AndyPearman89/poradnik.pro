<?php

namespace Poradnik\AfilacjaAdsense\Admin;

use Poradnik\AfilacjaAdsense\Affiliate\Infrastructure\AffiliateRepository;

class AffiliateLinksPage
{
    private AffiliateRepository $repository;

    public function __construct(AffiliateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets(): void
    {
        wp_enqueue_style('paa-affiliate-admin', PAA_URL . 'assets/css/affiliate.css', [], PAA_VERSION);
    }

    public function handleActions(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = isset($_POST['paa_action']) ? sanitize_text_field((string) wp_unslash($_POST['paa_action'])) : '';
        if ($action === 'save_link') {
            check_admin_referer('paa_save_link');
            $id = isset($_POST['id']) ? (int) wp_unslash($_POST['id']) : 0;
            $payload = [
                'title' => isset($_POST['title']) ? sanitize_text_field((string) wp_unslash($_POST['title'])) : '',
                'slug' => isset($_POST['slug']) ? sanitize_title((string) wp_unslash($_POST['slug'])) : '',
                'destination_url' => isset($_POST['destination_url']) ? esc_url_raw((string) wp_unslash($_POST['destination_url'])) : '',
                'category' => isset($_POST['category']) ? sanitize_text_field((string) wp_unslash($_POST['category'])) : '',
                'description' => isset($_POST['description']) ? sanitize_textarea_field((string) wp_unslash($_POST['description'])) : '',
                'button_text' => isset($_POST['button_text']) ? sanitize_text_field((string) wp_unslash($_POST['button_text'])) : 'SprawdĹş ofertÄ™',
                'image_url' => isset($_POST['image_url']) ? esc_url_raw((string) wp_unslash($_POST['image_url'])) : '',
            ];

            if ($id > 0) {
                $this->repository->update($id, $payload);
            } else {
                $this->repository->insert($payload);
            }
        }

        if (isset($_GET['paa_delete'])) {
            $nonce = isset($_GET['_wpnonce']) ? (string) wp_unslash($_GET['_wpnonce']) : '';
            if (wp_verify_nonce($nonce, 'paa_delete_link')) {
                $this->repository->delete((int) wp_unslash($_GET['paa_delete']));
            }
        }
    }

    public function renderPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $this->handleActions();

        $editId = isset($_GET['edit']) ? (int) wp_unslash($_GET['edit']) : 0;
        $editRow = $editId > 0 ? $this->repository->findById($editId) : null;
        $data = $editRow ? $editRow->toArray() : [
            'id' => 0,
            'title' => '',
            'slug' => '',
            'destination_url' => '',
            'category' => '',
            'description' => '',
            'button_text' => 'SprawdĹş ofertÄ™',
            'image_url' => '',
        ];

        $links = $this->repository->getAll();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Linki afiliacyjne', 'peartree-pro-afiliacja-adsense'); ?></h1>

            <h2><?php echo esc_html($editId > 0 ? __('Edytuj link', 'peartree-pro-afiliacja-adsense') : __('Dodaj link', 'peartree-pro-afiliacja-adsense')); ?></h2>
            <form method="post">
                <?php wp_nonce_field('paa_save_link'); ?>
                <input type="hidden" name="paa_action" value="save_link" />
                <input type="hidden" name="id" value="<?php echo esc_attr((string) ($data['id'] ?? 0)); ?>" />
                <table class="form-table" role="presentation">
                    <tr><th><label for="title">Tytuł</label></th><td><input class="regular-text" type="text" id="title" name="title" value="<?php echo esc_attr((string) ($data['title'] ?? '')); ?>" required></td></tr>
                    <tr><th><label for="slug">Slug</label></th><td><input class="regular-text" type="text" id="slug" name="slug" value="<?php echo esc_attr((string) ($data['slug'] ?? '')); ?>" required></td></tr>
                    <tr><th><label for="destination_url">Docelowy URL</label></th><td><input class="large-text" type="url" id="destination_url" name="destination_url" value="<?php echo esc_attr((string) ($data['destination_url'] ?? '')); ?>" required></td></tr>
                    <tr><th><label for="category">Kategoria</label></th><td><input class="regular-text" type="text" id="category" name="category" value="<?php echo esc_attr((string) ($data['category'] ?? '')); ?>"></td></tr>
                    <tr><th><label for="description">Opis</label></th><td><textarea class="large-text" id="description" name="description" rows="3"><?php echo esc_textarea((string) ($data['description'] ?? '')); ?></textarea></td></tr>
                    <tr><th><label for="button_text">Tekst przycisku</label></th><td><input class="regular-text" type="text" id="button_text" name="button_text" value="<?php echo esc_attr((string) ($data['button_text'] ?? 'SprawdĹş ofertÄ™')); ?>"></td></tr>
                    <tr><th><label for="image_url">URL obrazka (opcjonalnie)</label></th><td><input class="large-text" type="url" id="image_url" name="image_url" value="<?php echo esc_attr((string) ($data['image_url'] ?? '')); ?>"></td></tr>
                </table>
                <?php submit_button($editId > 0 ? __('Zaktualizuj link', 'peartree-pro-afiliacja-adsense') : __('Dodaj link', 'peartree-pro-afiliacja-adsense')); ?>
            </form>

            <hr>
            <h2><?php echo esc_html__('Lista linkĂłw', 'peartree-pro-afiliacja-adsense'); ?></h2>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Tytuł</th>
                    <th>Slug</th>
                    <th>URL /go/</th>
                    <th>Kliknięcia</th>
                    <th>Akcje</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($links)) : ?>
                    <tr><td colspan="6"><?php echo esc_html__('Brak linkĂłw.', 'peartree-pro-afiliacja-adsense'); ?></td></tr>
                <?php else :
                    foreach ($links as $row) :
                        $editUrl = add_query_arg(['page' => 'paa-affiliate-links', 'edit' => (int) $row['id']], admin_url('admin.php'));
                        $deleteUrl = wp_nonce_url(
                            add_query_arg(['page' => 'paa-affiliate-links', 'paa_delete' => (int) $row['id']], admin_url('admin.php')),
                            'paa_delete_link'
                        );
                        ?>
                        <tr>
                            <td><?php echo esc_html((string) (int) $row['id']); ?></td>
                            <td><?php echo esc_html((string) $row['title']); ?></td>
                            <td><?php echo esc_html((string) $row['slug']); ?></td>
                            <td><code><?php echo esc_html(home_url('/go/' . $row['slug'])); ?></code></td>
                            <td><?php echo esc_html((string) (int) $row['clicks']); ?></td>
                            <td>
                                <a class="button button-small" href="<?php echo esc_url($editUrl); ?>"><?php echo esc_html__('Edytuj', 'peartree-pro-afiliacja-adsense'); ?></a>
                                <a class="button button-small" href="<?php echo esc_url($deleteUrl); ?>" onclick="return confirm('Usunąć ten link?');"><?php echo esc_html__('Usuń', 'peartree-pro-afiliacja-adsense'); ?></a>
                            </td>
                        </tr>
                    <?php endforeach;
                endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function renderStatsPage(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $stats = $this->repository->getStats();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Statystyki kliknięć', 'peartree-pro-afiliacja-adsense'); ?></h1>
            <p><strong><?php echo esc_html__('Łącznie linków:', 'peartree-pro-afiliacja-adsense'); ?></strong> <?php echo esc_html((string) (int) ($stats['total_links'] ?? 0)); ?></p>
            <p><strong><?php echo esc_html__('Łącznie kliknięć:', 'peartree-pro-afiliacja-adsense'); ?></strong> <?php echo esc_html((string) (int) ($stats['total_clicks'] ?? 0)); ?></p>
            <h2><?php echo esc_html__('Top linki', 'peartree-pro-afiliacja-adsense'); ?></h2>
            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Tytuł</th><th>Slug</th><th>Kliknięcia</th></tr></thead>
                <tbody>
                <?php if (!empty($stats['top_links']) && is_array($stats['top_links'])) :
                    foreach ($stats['top_links'] as $row) : ?>
                        <tr>
                            <td><?php echo esc_html((string) (int) ($row['id'] ?? 0)); ?></td>
                            <td><?php echo esc_html((string) ($row['title'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) ($row['slug'] ?? '')); ?></td>
                            <td><?php echo esc_html((string) (int) ($row['clicks'] ?? 0)); ?></td>
                        </tr>
                    <?php endforeach;
                else : ?>
                    <tr><td colspan="4"><?php echo esc_html__('Brak danych.', 'peartree-pro-afiliacja-adsense'); ?></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}

