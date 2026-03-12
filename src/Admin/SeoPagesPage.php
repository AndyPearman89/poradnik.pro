<?php

namespace PearTree\ProgrammaticAffiliate\Admin;

use PearTree\ProgrammaticAffiliate\SEO\Application\SeoPageGenerator;
use PearTree\ProgrammaticAffiliate\SEO\Infrastructure\SeoPageRepository;

class SeoPagesPage
{
    private SeoPageRepository $repository;
    private SeoPageGenerator $generator;

    public function __construct(SeoPageRepository $repository, SeoPageGenerator $generator)
    {
        $this->repository = $repository;
        $this->generator = $generator;
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
        $pages = $this->repository->all();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Strony Programmatic SEO', 'peartree-pro-programmatic-affiliate'); ?></h1>
            <form method="post">
                <?php wp_nonce_field('ppae_generate_seo_page'); ?>
                <input type="hidden" name="ppae_action" value="generate_seo_page">
                <table class="form-table">
                    <tr><th>Słowo kluczowe</th><td><input class="regular-text" type="text" name="keyword" required></td></tr>
                    <tr><th>Slug</th><td><input class="regular-text" type="text" name="slug" required></td></tr>
                    <tr><th>Tytuł</th><td><input class="large-text" type="text" name="title" required></td></tr>
                    <tr><th>Kategoria</th><td><input class="regular-text" type="text" name="category" value="hosting"></td></tr>
                    <tr><th>Tekst wstępu</th><td><textarea class="large-text" rows="4" name="content_template"></textarea></td></tr>
                </table>
                <?php submit_button(__('Generuj stronę SEO', 'peartree-pro-programmatic-affiliate')); ?>
            </form>

            <table class="widefat striped">
                <thead><tr><th>ID</th><th>Słowo kluczowe</th><th>Slug</th><th>Tytuł</th><th>Strona WP</th></tr></thead>
                <tbody>
                <?php if (empty($pages)) : ?>
                    <tr><td colspan="5">Brak stron SEO.</td></tr>
                <?php else : foreach ($pages as $page) : ?>
                    <tr>
                        <td><?php echo esc_html((string) (int) ($page['id'] ?? 0)); ?></td>
                        <td><?php echo esc_html((string) ($page['keyword'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($page['slug'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) ($page['title'] ?? '')); ?></td>
                        <td><?php echo esc_html((string) (int) ($page['wp_page_id'] ?? 0)); ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function handleActions(): void
    {
        if (isset($_POST['ppae_action']) && (string) wp_unslash($_POST['ppae_action']) === 'generate_seo_page') {
            check_admin_referer('ppae_generate_seo_page');
            $this->generator->generate([
                'keyword' => isset($_POST['keyword']) ? wp_unslash($_POST['keyword']) : '',
                'slug' => isset($_POST['slug']) ? wp_unslash($_POST['slug']) : '',
                'title' => isset($_POST['title']) ? wp_unslash($_POST['title']) : '',
                'category' => isset($_POST['category']) ? wp_unslash($_POST['category']) : '',
                'content_template' => isset($_POST['content_template']) ? wp_unslash($_POST['content_template']) : '',
            ]);
        }
    }
}

