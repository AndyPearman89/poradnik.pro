<?php

namespace Poradnik\Platform\Domain\Seo;

use Poradnik\Platform\Core\ContentTypeMapper;
use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Domain\Seo\CategoryMap;
use Poradnik\Platform\Modules\AiImageGenerator\AiImageGeneratorService;
use WP_Query;

if (! defined('ABSPATH')) {
    exit;
}

final class ProgrammaticGenerator
{
    private const MAX_BATCH_TOPICS = 250;
    private const CLUSTER_POST_TYPES = ['guide', 'ranking', 'review', 'comparison'];

    /**
     * @return array<string, mixed>
     */
    public static function build(string $template, string $topic, int $count = 1, string $postType = 'guide'): array
    {
        $template = sanitize_key($template);
        $topic = trim(wp_strip_all_tags($topic));
        $postType = ContentTypeMapper::normalizePostType($postType, 'guide');

        $availableTemplates = array_keys(CategoryMap::templates());
        $availablePostTypes = array_keys(CategoryMap::postTypes());

        if (! in_array($template, $availableTemplates, true)) {
            $template = 'jak-zrobic';
        }

        if (! in_array($postType, $availablePostTypes, true)) {
            $postType = 'guide';
        }

        if ($topic === '' || $count < 1) {
            return ['created' => 0, 'items' => []];
        }

        $count = min($count, 50);
        $items = [];

        for ($index = 1; $index <= $count; $index++) {
            $title = self::buildTitle($template, $topic, $index);
            $content = self::buildContent($template, $topic, $title, $postType);

            $existingPostId = self::findExistingProgrammaticPost($postType, $template, $topic, $index, $title);
            if ($existingPostId > 0) {
                $items[] = [
                    'post_id' => $existingPostId,
                    'title' => $title,
                    'status' => 'existing',
                    'post_type' => $postType,
                    'template' => $template,
                ];

                EventLogger::dispatch('poradnik_programmatic_duplicate_skipped', [
                    'post_id' => $existingPostId,
                    'post_type' => $postType,
                    'template' => $template,
                    'topic' => $topic,
                    'index' => $index,
                ]);
                continue;
            }

            $postId = wp_insert_post([
                'post_type' => $postType,
                'post_status' => 'draft',
                'post_title' => $title,
                'post_content' => $content,
            ], true);

            if (is_wp_error($postId) || ! is_int($postId) || $postId < 1) {
                EventLogger::dispatch('poradnik_programmatic_insert_failed', [
                    'post_type' => $postType,
                    'template'  => $template,
                    'topic'     => $topic,
                    'index'     => $index,
                    'error'     => is_wp_error($postId) ? $postId->get_error_message() : 'invalid_post_id',
                ]);
                continue;
            }

            update_post_meta($postId, '_poradnik_programmatic_template', $template);
            update_post_meta($postId, '_poradnik_programmatic_topic', $topic);
            update_post_meta($postId, '_poradnik_programmatic_index', $index);
            update_post_meta($postId, '_poradnik_programmatic_post_type', $postType);
            update_post_meta($postId, '_poradnik_programmatic_cluster', wp_json_encode(self::buildClusterMap($topic)));

            $imageResult = ['ok' => false, 'queued' => false, 'error' => 'ai_image_service_missing'];
            if (class_exists(AiImageGeneratorService::class)) {
                $imageResult = AiImageGeneratorService::generateForPost($postId, false);

                if (empty($imageResult['ok'])) {
                    AiImageGeneratorService::queuePostGeneration($postId, false);
                    $imageResult['queued'] = true;
                }
            }

            $imageStatus = ! empty($imageResult['ok']) ? 'generated' : (! empty($imageResult['queued']) ? 'queued' : 'failed');
            update_post_meta($postId, '_poradnik_programmatic_image_status', $imageStatus);

            // Run QA immediately so failed posts are flagged before editorial review.
            $qa = QaGuardrails::check($postId);
            $qaStatus = 'draft';
            if (is_wp_error($qa)) {
                update_post_meta($postId, '_poradnik_qa_failed', $qa->get_error_message());
                $qaStatus = 'qa_failed';

                EventLogger::dispatch('poradnik_programmatic_qa_failed', [
                    'post_id' => $postId,
                    'issues'  => $qa->get_error_data()['issues'] ?? [],
                ]);
            }

            $items[] = [
                'post_id'   => $postId,
                'title'     => $title,
                'status'    => $qaStatus,
                'image_status' => $imageStatus,
                'post_type' => $postType,
                'template'  => $template,
            ];
        }

        $created = count(array_filter($items, static fn (array $item): bool => (string) ($item['status'] ?? '') !== 'existing'));

        return ['created' => $created, 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildBatch(string $template, int $count = 1, string $postType = 'guide', string $hub = 'all', int $maxTopics = self::MAX_BATCH_TOPICS): array
    {
        $topics = array_slice(CategoryMap::topicsForHub($hub), 0, max(1, min(self::MAX_BATCH_TOPICS, $maxTopics)));

        if ($topics === []) {
            return [
                'created' => 0,
                'topics_processed' => 0,
                'items' => [],
            ];
        }

        $created = 0;
        $items = [];

        foreach ($topics as $topic) {
            $result = self::build($template, $topic, $count, $postType);
            $created += (int) ($result['created'] ?? 0);

            $resultItems = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
            foreach ($resultItems as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (count($items) < 50) {
                    $items[] = $item;
                }
            }
        }

        return [
            'created' => $created,
            'topics_processed' => count($topics),
            'items' => $items,
            'items_preview_limited' => true,
            'hub' => $hub,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildCluster(string $topic, string $template = 'jak-zrobic', int $count = 1): array
    {
        $topic = trim(wp_strip_all_tags($topic));
        $count = 1;

        if ($topic === '') {
            return ['created' => 0, 'items' => []];
        }

        $cluster = self::buildClusterMap($topic);
        $items = [];
        $created = 0;

        foreach (self::CLUSTER_POST_TYPES as $postType) {
            $postTemplate = $postType === 'guide' ? $template : $postType;
            $postTopic = $cluster[$postType] ?? $topic;
            $result = self::build($postTemplate, $postTopic, $count, $postType);

            $created += (int) ($result['created'] ?? 0);

            $resultItems = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
            foreach ($resultItems as $item) {
                if (! is_array($item)) {
                    continue;
                }

                $item['cluster_post_type'] = $postType;
                $items[] = $item;
            }
        }

        self::linkClusterPosts($items, $topic);

        return [
            'created' => $created,
            'cluster_mode' => true,
            'topic' => $topic,
            'items' => $items,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function buildClusterBatch(string $template = 'jak-zrobic', string $hub = 'all', int $maxTopics = self::MAX_BATCH_TOPICS): array
    {
        $topics = array_slice(CategoryMap::topicsForHub($hub), 0, max(1, min(self::MAX_BATCH_TOPICS, $maxTopics)));

        if ($topics === []) {
            return [
                'created' => 0,
                'topics_processed' => 0,
                'items' => [],
            ];
        }

        $created = 0;
        $items = [];

        foreach ($topics as $topic) {
            $result = self::buildCluster($topic, $template, 1);
            $created += (int) ($result['created'] ?? 0);

            $resultItems = isset($result['items']) && is_array($result['items']) ? $result['items'] : [];
            foreach ($resultItems as $item) {
                if (! is_array($item)) {
                    continue;
                }

                if (count($items) < 50) {
                    $items[] = $item;
                }
            }
        }

        return [
            'created' => $created,
            'topics_processed' => count($topics),
            'items' => $items,
            'items_preview_limited' => true,
            'cluster_mode' => true,
            'hub' => $hub,
        ];
    }

    private static function buildTitle(string $template, string $topic, int $index): string
    {
        $year = gmdate('Y');

        return match ($template) {
            'jak-zrobic' => 'Jak zrobic ' . $topic . ' - poradnik ' . $index,
            'jak-ustawic' => 'Jak ustawic ' . $topic . ' - poradnik ' . $index,
            'jak-naprawic' => 'Jak naprawic ' . $topic . ' - poradnik ' . $index,
            'jak-wymienic' => 'Jak wymienic ' . $topic . ' - poradnik ' . $index,
            'jak-zainstalowac' => 'Jak zainstalowac ' . $topic . ' - poradnik ' . $index,
            'jak-wyczyscic' => 'Jak wyczyscic ' . $topic . ' - poradnik ' . $index,
            'jak-skonfigurowac' => 'Jak skonfigurowac ' . $topic . ' - poradnik ' . $index,
            'jak-dziala' => 'Jak dziala ' . $topic . ' - poradnik ' . $index,
            'best' => 'Najlepszy ' . $topic . ' ' . $year . ' (' . $index . ')',
            'ranking' => 'Ranking ' . $topic . ' - zestawienie ' . $index,
            default => 'Przewodnik: ' . $topic . ' - ' . $index,
        };
    }

    private static function buildContent(string $template, string $topic, string $title, string $postType): string
    {
        $cluster = self::buildClusterMap($topic);

        $outline = ContentEnhancer::maybeInjectToc(implode("\n", self::contentSectionsForPostType($template, $topic, $postType, $cluster)));

        return '<h1>' . esc_html($title) . '</h1>' . $outline . '<p>Template: ' . esc_html($template) . '</p>';
    }

    /**
     * @param array<string, string> $cluster
     * @return array<int, string>
     */
    private static function contentSectionsForPostType(string $template, string $topic, string $postType, array $cluster): array
    {
        $base = [
            '<h2>Wstep</h2>',
            '<p>Material programmatic dla tematu: ' . esc_html($topic) . '.</p>',
            '<p>Typ tresci: ' . esc_html($postType) . '.</p>',
            '<p>Szablon: ' . esc_html($template) . '.</p>',
        ];

        $clusterBlock = [
            '<h2>Klaster tresci</h2>',
            '<ul>'
                . '<li>Poradnik: ' . esc_html($cluster['guide']) . '</li>'
                . '<li>Ranking: ' . esc_html($cluster['ranking']) . '</li>'
                . '<li>Recenzja: ' . esc_html($cluster['review']) . '</li>'
                . '<li>Porownanie: ' . esc_html($cluster['comparison']) . '</li>'
                . '</ul>',
        ];

        $postTypeSections = match ($postType) {
            'guide' => [
                '<h2>Krok po kroku</h2>',
                '<ol><li>Zdefiniuj cel i zakres wdrozenia.</li><li>Przygotuj narzedzia i dane.</li><li>Wykonaj konfiguracje.</li><li>Przetestuj i udokumentuj wynik.</li></ol>',
                '<h2>Najczestsze bledy</h2>',
                '<p>Unikaj pomijania testow i brakujacej walidacji ustawien.</p>',
            ],
            'ranking' => [
                '<h2>Kryteria rankingu</h2>',
                '<p>Ocena obejmuje funkcje, cene, latwosc wdrozenia, wsparcie i skalowalnosc.</p>',
                '<h2>Top rozwiazania</h2>',
                '<p>Przed wyborem porownaj koszty calkowite i dostepne integracje.</p>',
            ],
            'review' => [
                '<h2>Szybka ocena</h2>',
                '<p>Material opisuje realne scenariusze, koszty i ograniczenia narzedzia.</p>',
                '<h2>Zalety i wady</h2>',
                '<p>Zestaw plusow i minusow pomoze dopasowac narzedzie do celu biznesowego.</p>',
            ],
            'comparison' => [
                '<h2>Kluczowe roznice</h2>',
                '<p>Porownaj funkcje, UX, ceny, limity i mozliwosci automatyzacji.</p>',
                '<h2>Kto wygrywa i dla kogo</h2>',
                '<p>Werdykt opiera sie o scenariusz uzycia: start, wzrost, skala.</p>',
            ],
            'tool' => [
                '<h2>Profil narzedzia</h2>',
                '<p>Opis zastosowan, modelu cenowego i integracji.</p>',
                '<h2>Dla kogo</h2>',
                '<p>Narzedzie sprawdzi sie w zespolach, ktore potrzebuja szybkiego wdrozenia.</p>',
            ],
            'news' => [
                '<h2>Co sie zmienilo</h2>',
                '<p>Podsumowanie aktualizacji i jej wplywu na SEO oraz workflow redakcji.</p>',
                '<h2>Co zrobic teraz</h2>',
                '<p>Zaktualizuj procesy i sprawdz widocznosc po wdrozeniu zmian.</p>',
            ],
            default => [
                '<h2>Kryteria wyboru</h2>',
                '<p>Przeanalizuj ceny, funkcje, wsparcie i skalowalnosc.</p>',
                '<h2>Podsumowanie</h2>',
                '<p>Wybierz opcje dopasowana do celu biznesowego.</p>',
            ],
        };

        return array_merge(
            $base,
            $postTypeSections,
            $clusterBlock,
            [
                '<h2>Podsumowanie</h2>',
                '<p>Przed publikacja uzupelnij dane redakcyjne, FAQ i linki afiliacyjne.</p>',
            ]
        );
    }

    /**
     * @return array<string, string>
     */
    public static function templates(): array
    {
        return CategoryMap::templates();
    }

    /**
     * @return array<string, string>
     */
    public static function postTypes(): array
    {
        return CategoryMap::postTypes();
    }

    /**
     * @return array<string, array{label:string, subcategories:array<int, string>}>
     */
    public static function hubs(): array
    {
        return CategoryMap::hubs();
    }

    /**
     * @return array<int, string>
     */
    public static function topics(): array
    {
        return CategoryMap::allTopics();
    }

    /**
     * @return array<string, string>
     */
    public static function hubOptions(): array
    {
        return CategoryMap::hubOptions();
    }

    private static function findExistingProgrammaticPost(string $postType, string $template, string $topic, int $index, string $title): int
    {
        $metaMatch = new WP_Query([
            'post_type' => $postType,
            'post_status' => ['draft', 'publish', 'pending', 'future', 'private'],
            'fields' => 'ids',
            'posts_per_page' => 1,
            'no_found_rows' => true,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => '_poradnik_programmatic_template',
                    'value' => $template,
                ],
                [
                    'key' => '_poradnik_programmatic_topic',
                    'value' => $topic,
                ],
                [
                    'key' => '_poradnik_programmatic_index',
                    'value' => $index,
                    'type' => 'NUMERIC',
                ],
            ],
        ]);

        if (! empty($metaMatch->posts)) {
            return (int) $metaMatch->posts[0];
        }

        $titleMatch = new WP_Query([
            'post_type' => $postType,
            'post_status' => ['draft', 'publish', 'pending', 'future', 'private'],
            'fields' => 'ids',
            'posts_per_page' => 5,
            'no_found_rows' => true,
            'title' => $title,
        ]);

        if (! empty($titleMatch->posts)) {
            return (int) $titleMatch->posts[0];
        }

        return 0;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private static function linkClusterPosts(array $items, string $topic): void
    {
        $clusterIds = [];

        foreach ($items as $item) {
            $postType = isset($item['cluster_post_type']) ? (string) $item['cluster_post_type'] : '';
            $postId = isset($item['post_id']) ? (int) $item['post_id'] : 0;

            if ($postType === '' || $postId < 1) {
                continue;
            }

            $clusterIds[$postType] = $postId;
        }

        if ($clusterIds === []) {
            return;
        }

        foreach ($clusterIds as $postType => $postId) {
            update_post_meta($postId, '_poradnik_cluster_topic', $topic);
            update_post_meta($postId, '_poradnik_cluster_role', $postType);
            update_post_meta($postId, '_poradnik_cluster_post_ids', wp_json_encode($clusterIds));

            foreach ($clusterIds as $relatedType => $relatedPostId) {
                update_post_meta($postId, '_poradnik_cluster_' . $relatedType . '_id', $relatedPostId);
            }

            $relatedIds = array_values(array_filter($clusterIds, static fn (int $relatedPostId): bool => $relatedPostId !== $postId));

            if ($postType === 'guide') {
                $toolIds = self::findRelatedToolIds($topic, $postId, 3);
                if ($toolIds !== []) {
                    $relatedIds = array_values(array_unique(array_merge($relatedIds, $toolIds)));
                }
            }

            update_post_meta($postId, 'related_articles', $relatedIds);
        }
    }

    /**
     * @return array<int, int>
     */
    private static function findRelatedToolIds(string $topic, int $excludePostId = 0, int $limit = 3): array
    {
        $topic = trim(wp_strip_all_tags($topic));
        if ($topic === '') {
            return [];
        }

        $query = new WP_Query([
            'post_type' => 'tool',
            'post_status' => ['draft', 'publish', 'pending', 'future', 'private'],
            'fields' => 'ids',
            'posts_per_page' => max(1, $limit),
            'no_found_rows' => true,
            's' => $topic,
            'post__not_in' => $excludePostId > 0 ? [$excludePostId] : [],
        ]);

        if (! is_array($query->posts) || $query->posts === []) {
            return [];
        }

        return array_values(array_filter(array_map('absint', $query->posts)));
    }

    /**
     * @return array<string, string>
     */
    private static function buildClusterMap(string $topic): array
    {
        return [
            'guide' => $topic,
            'ranking' => $topic,
            'review' => $topic,
            'comparison' => $topic,
        ];
    }
}
