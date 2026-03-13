<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Seo\ProgrammaticGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class ProgrammaticSeoPage
{
    private const PAGE_SLUG = 'poradnik-programmatic-seo';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_submenu_page(
            PlatformAdminPanel::MENU_SLUG,
            __('Poradnik Programmatic SEO', 'poradnik-platform'),
            __('Programmatic SEO', 'poradnik-platform'),
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

        $template = isset($_POST['template']) ? sanitize_key((string) wp_unslash($_POST['template'])) : 'how-to';
        $topic = isset($_POST['topic']) ? sanitize_text_field((string) wp_unslash($_POST['topic'])) : '';
        $count = isset($_POST['count']) ? absint($_POST['count']) : 1;
        $postType = isset($_POST['post_type']) ? sanitize_key((string) wp_unslash($_POST['post_type'])) : 'guide';

        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('poradnik_programmatic_build');
            $result = ProgrammaticGenerator::build($template, $topic, $count, $postType);
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Programmatic SEO Builder', 'poradnik-platform') . '</h1>';
        echo '<form method="post" action="" style="max-width: 960px;">';
        wp_nonce_field('poradnik_programmatic_build');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="programmatic-template">Template</label></th><td><select id="programmatic-template" name="template"><option value="how-to" ' . selected($template, 'how-to', false) . '>how-to</option><option value="best" ' . selected($template, 'best', false) . '>best</option><option value="ranking" ' . selected($template, 'ranking', false) . '>ranking</option></select></td></tr>';
        echo '<tr><th scope="row"><label for="programmatic-topic">Topic</label></th><td><input id="programmatic-topic" type="text" class="regular-text" name="topic" value="' . esc_attr($topic) . '" required /></td></tr>';
        echo '<tr><th scope="row"><label for="programmatic-count">Count</label></th><td><input id="programmatic-count" type="number" min="1" max="50" class="small-text" name="count" value="' . esc_attr((string) max(1, $count)) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="programmatic-post-type">Post Type</label></th><td><select id="programmatic-post-type" name="post_type"><option value="guide" ' . selected($postType, 'guide', false) . '>guide</option><option value="ranking" ' . selected($postType, 'ranking', false) . '>ranking</option><option value="comparison" ' . selected($postType, 'comparison', false) . '>comparison</option></select></td></tr>';
        echo '</table>';
        submit_button(__('Build Programmatic Drafts', 'poradnik-platform'));
        echo '</form>';

        if (is_array($result)) {
            $created = (int) ($result['created'] ?? 0);
            echo '<h2>' . esc_html__('Result', 'poradnik-platform') . '</h2>';
            echo '<p><strong>Created:</strong> ' . esc_html((string) $created) . '</p>';

            $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
            if ($items !== []) {
                echo '<table class="widefat striped" style="max-width:960px;"><thead><tr><th>Post ID</th><th>Title</th><th>Status</th></tr></thead><tbody>';
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    echo '<tr>';
                    echo '<td>' . esc_html((string) ($item['post_id'] ?? '')) . '</td>';
                    echo '<td>' . esc_html((string) ($item['title'] ?? '')) . '</td>';
                    echo '<td>' . esc_html((string) ($item['status'] ?? 'draft')) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
        }

        echo '</div>';
    }
}
