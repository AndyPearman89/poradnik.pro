<?php

namespace Poradnik\Platform\Admin;

use Poradnik\Platform\Core\Capabilities;
use Poradnik\Platform\Domain\Seo\CategoryMap;
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
        add_management_page(
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

        $generationMode = isset($_POST['generation_mode']) ? sanitize_key((string) wp_unslash($_POST['generation_mode'])) : 'single';
        $template = isset($_POST['template']) ? sanitize_key((string) wp_unslash($_POST['template'])) : 'jak-zrobic';
        $topic = isset($_POST['topic']) ? sanitize_text_field((string) wp_unslash($_POST['topic'])) : '';
        $count = isset($_POST['count']) ? absint(wp_unslash($_POST['count'])) : 1;
        $postType = isset($_POST['post_type']) ? sanitize_key((string) wp_unslash($_POST['post_type'])) : 'guide';
        $hub = isset($_POST['hub']) ? sanitize_key((string) wp_unslash($_POST['hub'])) : 'all';
        $maxTopics = isset($_POST['max_topics']) ? absint(wp_unslash($_POST['max_topics'])) : 25;

        $result = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            check_admin_referer('poradnik_programmatic_build');
            if ($generationMode === 'cluster') {
                $result = ProgrammaticGenerator::buildCluster($topic, $template, $count);
            } elseif ($generationMode === 'cluster-batch') {
                $result = ProgrammaticGenerator::buildClusterBatch($template, $hub, $maxTopics);
            } elseif ($generationMode === 'batch') {
                $result = ProgrammaticGenerator::buildBatch($template, $count, $postType, $hub, $maxTopics);
            } else {
                $result = ProgrammaticGenerator::build($template, $topic, $count, $postType);
            }
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Programmatic SEO Builder', 'poradnik-platform') . '</h1>';
        self::renderMapSummary();
        echo '<form method="post" action="" style="max-width: 960px;">';
        wp_nonce_field('poradnik_programmatic_build');
        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row">Tryb generacji</th><td>';
        echo '<label><input type="radio" name="generation_mode" value="single" ' . checked($generationMode, 'single', false) . ' /> ' . esc_html__('Jeden temat', 'poradnik-platform') . '</label><br />';
        echo '<label><input type="radio" name="generation_mode" value="batch" ' . checked($generationMode, 'batch', false) . ' /> ' . esc_html__('Batch z bazy tematow', 'poradnik-platform') . '</label>';
        echo '<br /><label><input type="radio" name="generation_mode" value="cluster" ' . checked($generationMode, 'cluster', false) . ' /> ' . esc_html__('Klaster dla jednego tematu', 'poradnik-platform') . '</label>';
        echo '<br /><label><input type="radio" name="generation_mode" value="cluster-batch" ' . checked($generationMode, 'cluster-batch', false) . ' /> ' . esc_html__('Batch klastrow z bazy tematow', 'poradnik-platform') . '</label>';
        echo '</td></tr>';
        echo '<tr><th scope="row"><label for="programmatic-template">Template</label></th><td><select id="programmatic-template" name="template">';
        foreach (ProgrammaticGenerator::templates() as $templateKey => $templateLabel) {
            echo '<option value="' . esc_attr($templateKey) . '" ' . selected($template, $templateKey, false) . '>' . esc_html($templateLabel) . '</option>';
        }
        echo '</select></td></tr>';
        echo '<tr><th scope="row"><label for="programmatic-topic">Topic</label></th><td><input id="programmatic-topic" type="text" class="regular-text" name="topic" value="' . esc_attr($topic) . '" list="poradnik-programmatic-topics" />';
        echo '<datalist id="poradnik-programmatic-topics">';
        foreach (ProgrammaticGenerator::topics() as $availableTopic) {
            echo '<option value="' . esc_attr($availableTopic) . '"></option>';
        }
        echo '</datalist>';
        echo '<p class="description">' . esc_html__('Dla trybu pojedynczego lub klastrowego wpisz temat lub wybierz go z listy.', 'poradnik-platform') . '</p></td></tr>';
        echo '<tr><th scope="row"><label for="programmatic-hub">Hub</label></th><td><select id="programmatic-hub" name="hub">';
        foreach (ProgrammaticGenerator::hubOptions() as $hubKey => $hubLabel) {
            echo '<option value="' . esc_attr($hubKey) . '" ' . selected($hub, $hubKey, false) . '>' . esc_html($hubLabel) . '</option>';
        }
        echo '</select><p class="description">' . esc_html__('Dla trybu batch lub cluster-batch wybierz caly hub albo wszystkie huby.', 'poradnik-platform') . '</p></td></tr>';
        echo '<tr><th scope="row"><label for="programmatic-max-topics">Max tematow</label></th><td><input id="programmatic-max-topics" type="number" min="1" max="250" class="small-text" name="max_topics" value="' . esc_attr((string) max(1, min(250, $maxTopics))) . '" /><p class="description">' . esc_html__('Batch jest ograniczony do 250 tematow na jedno uruchomienie, zeby nie blokowac requestu.', 'poradnik-platform') . '</p></td></tr>';
        echo '<tr><th scope="row"><label for="programmatic-count">Count</label></th><td><input id="programmatic-count" type="number" min="1" max="50" class="small-text" name="count" value="' . esc_attr((string) max(1, $count)) . '" /></td></tr>';
        echo '<tr><th scope="row"><label for="programmatic-post-type">Post Type</label></th><td><select id="programmatic-post-type" name="post_type">';
        foreach (ProgrammaticGenerator::postTypes() as $postTypeKey => $postTypeLabel) {
            echo '<option value="' . esc_attr($postTypeKey) . '" ' . selected($postType, $postTypeKey, false) . '>' . esc_html($postTypeLabel) . '</option>';
        }
        echo '</select></td></tr>';
        echo '</table>';
        submit_button(__('Build Programmatic Drafts', 'poradnik-platform'));
        echo '</form>';

        if (is_array($result)) {
            $created = (int) ($result['created'] ?? 0);
            echo '<h2>' . esc_html__('Result', 'poradnik-platform') . '</h2>';
            echo '<p><strong>Created:</strong> ' . esc_html((string) $created) . '</p>';

            if (isset($result['topics_processed'])) {
                echo '<p><strong>' . esc_html__('Topics processed:', 'poradnik-platform') . '</strong> ' . esc_html((string) $result['topics_processed']) . '</p>';
            }

            if (! empty($result['cluster_mode'])) {
                echo '<p><strong>' . esc_html__('Cluster mode:', 'poradnik-platform') . '</strong> ' . esc_html__('guide + ranking + review + comparison', 'poradnik-platform') . '</p>';
            }

            if (! empty($result['items_preview_limited'])) {
                echo '<p class="description">' . esc_html__('Tabela ponizej pokazuje tylko pierwsze 50 rekordow z batcha.', 'poradnik-platform') . '</p>';
            }

            $items = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
            if ($items !== []) {
                echo '<table class="widefat striped" style="max-width:960px;"><thead><tr><th>Post ID</th><th>Title</th><th>Status</th><th>Type</th></tr></thead><tbody>';
                foreach ($items as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    echo '<tr>';
                    echo '<td>' . esc_html((string) ($item['post_id'] ?? '')) . '</td>';
                    echo '<td>' . esc_html((string) ($item['title'] ?? '')) . '</td>';
                    echo '<td>' . esc_html((string) ($item['status'] ?? 'draft')) . '</td>';
                    echo '<td>' . esc_html((string) ($item['cluster_post_type'] ?? $postType)) . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
        }

        echo '</div>';
    }

    private static function renderMapSummary(): void
    {
        $summary = CategoryMap::summary();
        $hubs = ProgrammaticGenerator::hubs();

        echo '<div class="notice notice-info" style="padding:12px 16px; margin:16px 0;">';
        echo '<p><strong>' . esc_html__('Category Map', 'poradnik-platform') . ':</strong> '
            . esc_html((string) $summary['hub_count']) . ' hubow, '
            . esc_html((string) $summary['subcategory_count']) . ' podkategorii, '
            . esc_html((string) $summary['template_count']) . ' typow poradnikow, '
            . esc_html((string) $summary['base_topic_count']) . ' tematow w aktualnej bazie, '
            . esc_html((string) $summary['current_projected_articles']) . ' artykulow z aktualnej bazy, '
            . esc_html((string) $summary['recommended_projected_articles']) . ' artykulow przy bazie 250 tematow, '
            . esc_html((string) $summary['scale_projected_articles']) . ' artykulow przy bazie 6000 tematow.'
            . '</p>';
        echo '<p>'
            . esc_html__('Plan publikacji:', 'poradnik-platform') . ' '
            . esc_html((string) $summary['daily_publication_target']) . ' / dzien, '
            . esc_html((string) $summary['monthly_publication_target']) . ' / miesiac, '
            . esc_html((string) $summary['yearly_publication_target']) . ' / rok.'
            . '</p>';
        echo '</div>';

        echo '<details style="max-width:960px; margin-bottom:16px;"><summary><strong>' . esc_html__('Available SEO Hubs', 'poradnik-platform') . '</strong></summary>';
        echo '<table class="widefat striped" style="margin-top:12px;"><thead><tr><th>Hub</th><th>Subcategories</th></tr></thead><tbody>';

        foreach ($hubs as $hub) {
            echo '<tr>';
            echo '<td>' . esc_html($hub['label']) . '</td>';
            echo '<td>' . esc_html(implode(', ', $hub['subcategories'])) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</details>';

        echo '<details style="max-width:960px; margin-bottom:16px;"><summary><strong>' . esc_html__('Base Topics Bank', 'poradnik-platform') . '</strong></summary>';
        echo '<table class="widefat striped" style="margin-top:12px;"><thead><tr><th>Hub</th><th>Topics</th></tr></thead><tbody>';

        foreach (CategoryMap::topicBank() as $hubKey => $topics) {
            $hubLabel = $hubs[$hubKey]['label'] ?? $hubKey;
            echo '<tr>';
            echo '<td>' . esc_html($hubLabel) . '</td>';
            echo '<td>' . esc_html(implode(', ', $topics)) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</details>';
    }
}
