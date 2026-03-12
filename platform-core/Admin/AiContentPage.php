<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Ai\ContentGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class AiContentPage
{
    private const PAGE_SLUG = 'poradnik-ai-content';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik AI Article Assistant', 'poradnik-platform'),
            __('AI Article Assistant', 'poradnik-platform'),
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

        $tool = isset($_POST['tool']) ? sanitize_key((string) wp_unslash($_POST['tool'])) : 'outline';
        $input = isset($_POST['input']) ? (string) wp_unslash($_POST['input']) : '';

        $output = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('poradnik_ai_content_generate');
            $result = ContentGenerator::generate($tool, $input);
            $output = (string) ($result['output'] ?? '');
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('AI Article Assistant', 'poradnik-platform') . '</h1>';
        echo '<form method="post" action="" style="max-width: 960px;">';
        wp_nonce_field('poradnik_ai_content_generate');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="poradnik-ai-tool">Tool</label></th><td><select id="poradnik-ai-tool" name="tool">';

        foreach (ContentGenerator::tools() as $name) {
            echo '<option value="' . esc_attr($name) . '" ' . selected($tool, $name, false) . '>' . esc_html($name) . '</option>';
        }

        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="poradnik-ai-input">Input</label></th><td><textarea id="poradnik-ai-input" name="input" rows="5" class="large-text" required>' . esc_textarea($input) . '</textarea></td></tr>';
        echo '</table>';
        submit_button(__('Generate', 'poradnik-platform'));
        echo '</form>';

        if ($output !== '') {
            echo '<h2>' . esc_html__('Output', 'poradnik-platform') . '</h2>';
            echo '<textarea rows="14" class="large-text code" readonly>' . esc_textarea($output) . '</textarea>';
        }

        echo '</div>';
    }
}
