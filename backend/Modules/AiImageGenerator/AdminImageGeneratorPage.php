<?php

namespace Poradnik\Platform\Modules\AiImageGenerator;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Infrastructure\Database\Migrator;

if (! defined('ABSPATH')) {
    exit;
}

final class AdminImageGeneratorPage
{
    private const PAGE_SLUG = 'poradnik-ai-image-generator';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('AI Image Generator (Queue)', 'poradnik-platform'),
            __('AI Image Generator', 'poradnik-platform'),
            Capabilities::manageCapability(),
            self::PAGE_SLUG,
            [self::class, 'render']
        );
    }

    public static function render(): void
    {
        if (! Capabilities::canManagePlatform()) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'poradnik-platform'));
        }

        $result = null;
        $batchLimit = isset($_POST['batch_limit']) ? max(1, min(500, absint((string) wp_unslash($_POST['batch_limit'])))) : 100;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('poradnik_ai_image_generator_admin');
            $action = isset($_POST['generator_action']) ? sanitize_key((string) wp_unslash($_POST['generator_action'])) : '';

            if ($action === 'generate-missing') {
                $result = self::enqueueMissing($batchLimit, false);
            } elseif ($action === 'regenerate') {
                $result = self::enqueueMissing($batchLimit, true);
            } elseif ($action === 'run-worker') {
                ImageQueueWorker::run();
                $result = ['enqueued' => 0, 'mode' => 'run-worker'];
            }
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('AI Image Generator', 'poradnik-platform') . '</h1>';
        echo '<p>' . esc_html__('Workflow: save_post -> queue -> worker (10 images/min).', 'poradnik-platform') . '</p>';

        if (is_array($result)) {
            echo '<div class="notice notice-success"><p>'
                . esc_html(sprintf('Done: %d | Mode: %s', (int) ($result['enqueued'] ?? 0), (string) ($result['mode'] ?? '-')))
                . '</p></div>';
        }

        echo '<form method="post" action="">';
        wp_nonce_field('poradnik_ai_image_generator_admin');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="batch_limit">Batch limit</label></th><td><input id="batch_limit" type="number" class="small-text" name="batch_limit" min="1" max="500" value="' . esc_attr((string) $batchLimit) . '" /> <p class="description">Maksymalnie 500 postów na akcję.</p></td></tr>';
        echo '</table>';

        echo '<p>';
        echo '<button class="button button-primary" type="submit" name="generator_action" value="generate-missing">' . esc_html__('Generate Missing Images', 'poradnik-platform') . '</button> ';
        echo '<button class="button" type="submit" name="generator_action" value="regenerate">' . esc_html__('Regenerate Images', 'poradnik-platform') . '</button> ';
        echo '<button class="button" type="submit" name="generator_action" value="run-worker">' . esc_html__('Run Worker Now', 'poradnik-platform') . '</button>';
        echo '</p>';
        echo '</form>';

        self::renderTemplatePreview();
        self::renderQueueStats();
        self::renderRecentImages();

        echo '</div>';
    }

    /**
     * @return array<string, mixed>
     */
    private static function enqueueMissing(int $limit, bool $force): array
    {
        $query = new \WP_Query([
            'post_type' => ['guide', 'ranking', 'review', 'comparison', 'news'],
            'post_status' => ['publish', 'draft', 'pending', 'future'],
            'fields' => 'ids',
            'posts_per_page' => $limit,
            'orderby' => 'ID',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);

        $count = 0;
        foreach ((array) $query->posts as $postId) {
            $postId = (int) $postId;
            if ($postId < 1) {
                continue;
            }

            if (! $force) {
                $hasFeatured = (int) get_post_meta($postId, 'featured_image', true) > 0 || has_post_thumbnail($postId);
                $hasOg = (int) get_post_meta($postId, 'og_image', true) > 0;
                $hasSocial = (int) get_post_meta($postId, 'social_image', true) > 0;

                if ($hasFeatured && $hasOg && $hasSocial) {
                    continue;
                }
            }

            AiImageGeneratorService::queuePostGeneration($postId, $force);
            $count++;
        }

        return ['enqueued' => $count, 'mode' => $force ? 'regenerate' : 'generate-missing'];
    }

    private static function renderTemplatePreview(): void
    {
        echo '<h2>' . esc_html__('Preview Template Styles', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:960px;"><thead><tr><th>Type</th><th>Style</th><th>Color</th></tr></thead><tbody>';

        foreach (ImageTemplateEngine::styles() as $type => $style) {
            $color = ImageTemplateEngine::colorForType($type);
            echo '<tr>';
            echo '<td>' . esc_html($type) . '</td>';
            echo '<td>' . esc_html($style) . '</td>';
            echo '<td><code>' . esc_html($color) . '</code></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private static function renderQueueStats(): void
    {
        global $wpdb;
        $table = Migrator::tableName('image_generation_queue');

        $rows = $wpdb->get_results("SELECT status, COUNT(*) AS total FROM {$table} GROUP BY status", ARRAY_A);
        if (! is_array($rows)) {
            $rows = [];
        }

        echo '<h2>' . esc_html__('Queue Stats', 'poradnik-platform') . '</h2>';
        echo '<table class="widefat striped" style="max-width:560px;"><thead><tr><th>Status</th><th>Total</th></tr></thead><tbody>';

        if ($rows === []) {
            echo '<tr><td colspan="2">' . esc_html__('No queue records.', 'poradnik-platform') . '</td></tr>';
        } else {
            foreach ($rows as $row) {
                echo '<tr><td>' . esc_html((string) ($row['status'] ?? '')) . '</td><td>' . esc_html((string) ($row['total'] ?? '0')) . '</td></tr>';
            }
        }

        echo '</tbody></table>';
    }

    private static function renderRecentImages(): void
    {
        $attachments = get_posts([
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 24,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_key' => '_poradnik_ai_image_variant',
            'fields' => 'ids',
        ]);

        echo '<h2>' . esc_html__('Recent Generated Images', 'poradnik-platform') . '</h2>';

        if (! is_array($attachments) || $attachments === []) {
            echo '<p>' . esc_html__('No generated images found yet.', 'poradnik-platform') . '</p>';
            return;
        }

        echo '<table class="widefat striped" style="max-width:1160px;"><thead><tr><th>Preview</th><th>Variant</th><th>Type</th><th>File</th><th>Post</th><th>Media</th></tr></thead><tbody>';

        foreach ($attachments as $attachmentId) {
            $attachmentId = (int) $attachmentId;
            if ($attachmentId < 1) {
                continue;
            }

            $variant = (string) get_post_meta($attachmentId, '_poradnik_ai_image_variant', true);
            $type = (string) get_post_meta($attachmentId, '_poradnik_ai_image_type', true);
            $sourcePostId = (int) get_post_meta($attachmentId, '_poradnik_ai_source_post_id', true);
            $file = basename((string) get_attached_file($attachmentId));
            $thumb = wp_get_attachment_image($attachmentId, [120, 120], true, ['style' => 'width:120px;height:auto;border-radius:6px;']);
            $mediaEditUrl = get_edit_post_link($attachmentId);
            $mediaUrl = wp_get_attachment_url($attachmentId);

            echo '<tr>';
            echo '<td>' . ($thumb !== '' ? $thumb : '-') . '</td>';
            echo '<td>' . esc_html($variant !== '' ? $variant : '-') . '</td>';
            echo '<td>' . esc_html($type !== '' ? $type : '-') . '</td>';
            echo '<td><code>' . esc_html($file !== '' ? $file : '-') . '</code></td>';

            if ($sourcePostId > 0) {
                $sourceTitle = get_the_title($sourcePostId);
                $sourceEdit = get_edit_post_link($sourcePostId);
                $label = $sourceTitle !== '' ? $sourceTitle : ('#' . $sourcePostId);
                echo '<td>' . ($sourceEdit ? '<a href="' . esc_url($sourceEdit) . '">' . esc_html($label) . '</a>' : esc_html($label)) . '</td>';
            } else {
                echo '<td>-</td>';
            }

            $links = [];
            if (is_string($mediaEditUrl) && $mediaEditUrl !== '') {
                $links[] = '<a href="' . esc_url($mediaEditUrl) . '">' . esc_html__('Edit', 'poradnik-platform') . '</a>';
            }
            if (is_string($mediaUrl) && $mediaUrl !== '') {
                $links[] = '<a href="' . esc_url($mediaUrl) . '" target="_blank" rel="noopener">' . esc_html__('Open', 'poradnik-platform') . '</a>';
            }

            echo '<td>' . ($links !== [] ? implode(' | ', $links) : '-') . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }
}
