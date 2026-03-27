<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Guide\GuideGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class GuideGeneratorPage
{
    private const PAGE_SLUG    = 'poradnik-guide-generator';
    private const NONCE_ACTION = 'poradnik_guide_generator_generate';

    public static function init(): void
    {
        add_action('admin_menu', [self::class, 'registerPage']);
    }

    public static function registerPage(): void
    {
        add_management_page(
            __('Poradnik Guide Generator', 'poradnik-platform'),
            __('Guide Generator', 'poradnik-platform'),
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

        $generationMode = isset($_POST['generation_mode'])
            ? sanitize_key((string) wp_unslash($_POST['generation_mode']))
            : 'single';

        $topic = isset($_POST['topic'])
            ? sanitize_text_field((string) wp_unslash($_POST['topic']))
            : '';

        $guideType = isset($_POST['guide_type'])
            ? sanitize_key((string) wp_unslash($_POST['guide_type']))
            : 'jak_zrobic';

        $difficulty = isset($_POST['difficulty'])
            ? sanitize_key((string) wp_unslash($_POST['difficulty']))
            : 'medium';

        $estimatedTime = isset($_POST['estimated_time'])
            ? absint(wp_unslash($_POST['estimated_time']))
            : 30;

        $topicsList = isset($_POST['topics_list'])
            ? sanitize_textarea_field((string) wp_unslash($_POST['topics_list']))
            : '';

        $batchCount = isset($_POST['batch_count'])
            ? absint(wp_unslash($_POST['batch_count']))
            : 10;

        $createPost = ! empty($_POST['create_post']);

        /** @var array<string, mixed>|null $result */
        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer(self::NONCE_ACTION);

            if ($generationMode === 'batch') {
                $result = self::runBatch($topicsList, $guideType, $difficulty, $estimatedTime, $batchCount, $createPost);
            } else {
                $result = self::runSingle($topic, $guideType, $difficulty, $estimatedTime, $createPost);
            }
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Guide Generator', 'poradnik-platform') . '</h1>';

        self::renderSupportedTypes();

        echo '<form method="post" action="" style="max-width: 960px;">';
        wp_nonce_field(self::NONCE_ACTION);

        echo '<table class="form-table" role="presentation">';

        // Generation mode
        echo '<tr><th scope="row">' . esc_html__('Generation mode', 'poradnik-platform') . '</th><td>';
        echo '<label><input type="radio" name="generation_mode" value="single" ' . checked($generationMode, 'single', false) . ' /> ' . esc_html__('Single guide', 'poradnik-platform') . '</label>';
        echo '&nbsp;&nbsp;<label><input type="radio" name="generation_mode" value="batch" ' . checked($generationMode, 'batch', false) . ' /> ' . esc_html__('Batch from topic list', 'poradnik-platform') . '</label>';
        echo '</td></tr>';

        // Single: topic
        echo '<tr><th scope="row"><label for="poradnik-guide-topic">' . esc_html__('Topic (single)', 'poradnik-platform') . '</label></th>';
        echo '<td><input id="poradnik-guide-topic" type="text" name="topic" class="regular-text" value="' . esc_attr($topic) . '" />';
        echo '<p class="description">' . esc_html__('Required for Single mode.', 'poradnik-platform') . '</p></td></tr>';

        // Batch: topics list
        echo '<tr><th scope="row"><label for="poradnik-guide-topics-list">' . esc_html__('Topics list (batch)', 'poradnik-platform') . '</label></th>';
        echo '<td><textarea id="poradnik-guide-topics-list" name="topics_list" rows="6" class="large-text">' . esc_textarea($topicsList) . '</textarea>';
        echo '<p class="description">' . esc_html__('One topic per line. Used in Batch mode.', 'poradnik-platform') . '</p></td></tr>';

        // Batch: count cap
        echo '<tr><th scope="row"><label for="poradnik-guide-batch-count">' . esc_html__('Max guides (batch)', 'poradnik-platform') . '</label></th>';
        echo '<td><input id="poradnik-guide-batch-count" type="number" name="batch_count" min="1" max="100" class="small-text" value="' . esc_attr((string) max(1, min(100, $batchCount))) . '" />';
        echo '<p class="description">' . esc_html__('Capped at 100 per run to avoid timeout.', 'poradnik-platform') . '</p></td></tr>';

        // Guide type
        echo '<tr><th scope="row"><label for="poradnik-guide-type">' . esc_html__('Guide type', 'poradnik-platform') . '</label></th>';
        echo '<td><select id="poradnik-guide-type" name="guide_type">';
        foreach (GuideGenerator::supportedGuideTypes() as $guideTypeOption) {
            echo '<option value="' . esc_attr($guideTypeOption) . '" ' . selected($guideType, $guideTypeOption, false) . '>'
                . esc_html($guideTypeOption)
                . '</option>';
        }
        echo '</select></td></tr>';

        // Difficulty
        echo '<tr><th scope="row"><label for="poradnik-guide-difficulty">' . esc_html__('Difficulty', 'poradnik-platform') . '</label></th>';
        echo '<td><select id="poradnik-guide-difficulty" name="difficulty">';
        foreach (['easy' => __('Easy', 'poradnik-platform'), 'medium' => __('Medium', 'poradnik-platform'), 'hard' => __('Hard', 'poradnik-platform')] as $diffKey => $diffLabel) {
            echo '<option value="' . esc_attr($diffKey) . '" ' . selected($difficulty, $diffKey, false) . '>' . esc_html($diffLabel) . '</option>';
        }
        echo '</select></td></tr>';

        // Estimated time
        echo '<tr><th scope="row"><label for="poradnik-guide-estimated-time">' . esc_html__('Estimated time (min)', 'poradnik-platform') . '</label></th>';
        echo '<td><input id="poradnik-guide-estimated-time" type="number" name="estimated_time" min="1" max="720" class="small-text" value="' . esc_attr((string) max(1, $estimatedTime)) . '" /></td></tr>';

        // Create post
        echo '<tr><th scope="row">' . esc_html__('Save as draft', 'poradnik-platform') . '</th>';
        echo '<td><label><input type="checkbox" name="create_post" value="1" ' . checked($createPost, true, false) . ' /> '
            . esc_html__('Create WP draft post for each generated guide', 'poradnik-platform')
            . '</label></td></tr>';

        echo '</table>';
        submit_button(__('Generate Guide(s)', 'poradnik-platform'));
        echo '</form>';

        if (is_array($result)) {
            self::renderResult($result, $generationMode);
        }

        echo '</div>';
    }

    /**
     * @return array<string, mixed>
     */
    private static function runSingle(
        string $topic,
        string $guideType,
        string $difficulty,
        int $estimatedTime,
        bool $createPost
    ): array {
        if ($topic === '') {
            return ['error' => __('Topic is required for Single mode.', 'poradnik-platform')];
        }

        $draft = GuideGenerator::buildDraft([
            'topic'          => $topic,
            'guide_type'     => $guideType,
            'difficulty'     => $difficulty,
            'estimated_time' => $estimatedTime,
        ]);

        $postId = 0;

        if ($createPost) {
            $saved = GuideGenerator::saveDraft($draft, get_current_user_id());

            if (is_wp_error($saved)) {
                return ['error' => $saved->get_error_message()];
            }

            $postId = (int) $saved;
        }

        return [
            'mode'    => 'single',
            'created' => $createPost ? 1 : 0,
            'items'   => [
                [
                    'post_id' => $postId,
                    'title'   => $draft['title'],
                    'status'  => $createPost ? 'draft_saved' : 'generated',
                    'type'    => $guideType,
                ],
            ],
            'draft'   => $draft,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function runBatch(
        string $topicsList,
        string $guideType,
        string $difficulty,
        int $estimatedTime,
        int $batchCount,
        bool $createPost
    ): array {
        $lines = preg_split('/\r\n|\r|\n/', $topicsList) ?: [];

        $topics = [];
        foreach ($lines as $line) {
            $trimmed = sanitize_text_field(trim((string) $line));
            if ($trimmed !== '') {
                $topics[] = $trimmed;
            }

            if (count($topics) >= $batchCount) {
                break;
            }
        }

        if ($topics === []) {
            return ['error' => __('Topics list is empty. Add at least one topic per line.', 'poradnik-platform')];
        }

        $items   = [];
        $created = 0;

        foreach ($topics as $topic) {
            $draft = GuideGenerator::buildDraft([
                'topic'          => $topic,
                'guide_type'     => $guideType,
                'difficulty'     => $difficulty,
                'estimated_time' => $estimatedTime,
            ]);

            $postId = 0;

            if ($createPost) {
                $saved = GuideGenerator::saveDraft($draft, get_current_user_id());

                if (! is_wp_error($saved)) {
                    $postId = (int) $saved;
                    ++$created;
                }
            }

            $items[] = [
                'post_id' => $postId,
                'title'   => $draft['title'],
                'status'  => $createPost ? ($postId > 0 ? 'draft_saved' : 'save_failed') : 'generated',
                'type'    => $guideType,
            ];
        }

        return [
            'mode'              => 'batch',
            'created'           => $created,
            'topics_processed'  => count($topics),
            'items'             => $items,
        ];
    }

    /**
     * @param array<string, mixed> $result
     */
    private static function renderResult(array $result, string $generationMode): void
    {
        if (isset($result['error'])) {
            echo '<div class="notice notice-error"><p>' . esc_html((string) $result['error']) . '</p></div>';
            return;
        }

        echo '<h2>' . esc_html__('Result', 'poradnik-platform') . '</h2>';
        echo '<p><strong>' . esc_html__('Created:', 'poradnik-platform') . '</strong> ' . esc_html((string) ($result['created'] ?? 0)) . '</p>';

        if (isset($result['topics_processed'])) {
            echo '<p><strong>' . esc_html__('Topics processed:', 'poradnik-platform') . '</strong> ' . esc_html((string) $result['topics_processed']) . '</p>';
        }

        $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];

        if ($items !== []) {
            echo '<table class="widefat striped" style="max-width:960px;">';
            echo '<thead><tr><th>' . esc_html__('Post ID', 'poradnik-platform') . '</th><th>' . esc_html__('Title', 'poradnik-platform') . '</th><th>' . esc_html__('Status', 'poradnik-platform') . '</th><th>' . esc_html__('Type', 'poradnik-platform') . '</th></tr></thead>';
            echo '<tbody>';

            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }

                echo '<tr>';
                echo '<td>' . esc_html((string) ($item['post_id'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($item['title'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($item['status'] ?? '')) . '</td>';
                echo '<td>' . esc_html((string) ($item['type'] ?? '')) . '</td>';
                echo '</tr>';
            }

            echo '</tbody></table>';
        }

        // For single mode, show the full draft output
        if ($generationMode === 'single' && isset($result['draft']) && is_array($result['draft'])) {
            $draft = $result['draft'];

            echo '<h2>' . esc_html__('Draft Output', 'poradnik-platform') . '</h2>';
            echo '<p><strong>' . esc_html__('Title:', 'poradnik-platform') . '</strong> ' . esc_html((string) ($draft['title'] ?? '')) . '</p>';
            echo '<p><strong>' . esc_html__('Intro:', 'poradnik-platform') . '</strong> ' . esc_html((string) ($draft['intro'] ?? '')) . '</p>';
            echo '<p><strong>' . esc_html__('Meta description:', 'poradnik-platform') . '</strong> ' . esc_html((string) ($draft['meta_description'] ?? '')) . '</p>';

            $steps = isset($draft['steps']) && is_array($draft['steps']) ? $draft['steps'] : [];
            if ($steps !== []) {
                echo '<h3>' . esc_html__('Steps', 'poradnik-platform') . '</h3>';
                echo '<ol>';
                foreach ($steps as $step) {
                    if (! is_array($step)) {
                        continue;
                    }
                    echo '<li><strong>' . esc_html((string) ($step['title'] ?? '')) . '</strong>';
                    $desc = trim((string) ($step['description'] ?? ''));
                    if ($desc !== '') {
                        echo ' — ' . esc_html($desc);
                    }
                    echo '</li>';
                }
                echo '</ol>';
            }

            $faq = isset($draft['faq']) && is_array($draft['faq']) ? $draft['faq'] : [];
            if ($faq !== []) {
                echo '<h3>' . esc_html__('FAQ', 'poradnik-platform') . '</h3>';
                echo '<dl>';
                foreach ($faq as $row) {
                    if (! is_array($row)) {
                        continue;
                    }
                    echo '<dt>' . esc_html((string) ($row['question'] ?? '')) . '</dt>';
                    echo '<dd>' . esc_html((string) ($row['answer'] ?? '')) . '</dd>';
                }
                echo '</dl>';
            }
        }
    }

    private static function renderSupportedTypes(): void
    {
        $types = GuideGenerator::supportedGuideTypes();

        echo '<div class="notice notice-info" style="padding:12px 16px; margin:16px 0;">';
        echo '<p><strong>' . esc_html__('Supported guide types:', 'poradnik-platform') . '</strong> ';
        echo esc_html(implode(', ', $types));
        echo '</p>';
        echo '</div>';
    }
}
