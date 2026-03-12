<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Ai\ImageGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class AiImagePage
{
    private const PAGE_SLUG = 'poradnik-ai-image';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik AI Image Generator', 'poradnik-platform'),
            __('AI Image Generator', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    public static function renderPage(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $title = isset($_POST['title']) ? (string) wp_unslash($_POST['title']) : '';
        $category = isset($_POST['category']) ? sanitize_key((string) wp_unslash($_POST['category'])) : 'general';

        $generated = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('poradnik_ai_image_generate');
            $generated = ImageGenerator::generateFromTitle($title, $category);
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('AI Image Generator', 'poradnik-platform') . '</h1>';
        echo '<form method="post" action="" style="max-width: 960px;">';
        wp_nonce_field('poradnik_ai_image_generate');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="poradnik-ai-image-title">Article Title</label></th><td><input id="poradnik-ai-image-title" name="title" type="text" class="large-text" value="' . esc_attr($title) . '" required /></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ai-image-category">Category style</label></th><td><select id="poradnik-ai-image-category" name="category"><option value="general" ' . selected($category, 'general', false) . '>general</option><option value="hosting" ' . selected($category, 'hosting', false) . '>hosting</option><option value="seo" ' . selected($category, 'seo', false) . '>seo</option><option value="finance" ' . selected($category, 'finance', false) . '>finance</option></select></td></tr>';
        echo '</table>';
        submit_button(__('Generate Images', 'poradnik-platform'));
        echo '</form>';

        if ($generated !== []) {
            echo '<h2>' . esc_html__('Generated Files', 'poradnik-platform') . '</h2>';
            echo '<table class="widefat striped" style="max-width: 960px;"><thead><tr><th>Variant</th><th>Attachment ID</th><th>URL</th><th>Status</th></tr></thead><tbody>';
            foreach ($generated as $variant => $row) {
                $id = (int) ($row['id'] ?? 0);
                $url = (string) ($row['url'] ?? '');
                $status = ((string) ($row['error'] ?? '') === '') ? 'ok' : (string) $row['error'];

                echo '<tr>';
                echo '<td>' . esc_html((string) $variant) . '</td>';
                echo '<td>' . esc_html((string) $id) . '</td>';
                echo '<td>' . ($url !== '' ? '<a href="' . esc_url($url) . '" target="_blank" rel="noopener">open</a>' : '-') . '</td>';
                echo '<td>' . esc_html($status) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '</div>';
    }
}
