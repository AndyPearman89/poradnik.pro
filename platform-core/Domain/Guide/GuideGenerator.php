<?php

namespace Poradnik\Platform\Domain\Guide;

use Poradnik\Platform\Domain\Ai\ContentGenerator;
use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

final class GuideGenerator
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_GUIDE_TYPES = [
        'jak_zrobic',
        'jak_ustawic',
        'jak_wymienic',
        'jak_naprawic',
        'jak_skonfigurowac',
        'jak_wyczyscic',
        'jak_zainstalowac',
    ];

    /**
     * @return array<int, string>
     */
    public static function supportedGuideTypes(): array
    {
        return self::SUPPORTED_GUIDE_TYPES;
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function buildDraft(array $input): array
    {
        $topic = trim(sanitize_text_field((string) ($input['topic'] ?? '')));
        $guideType = sanitize_key((string) ($input['guide_type'] ?? 'jak_zrobic'));
        $difficulty = trim(sanitize_text_field((string) ($input['difficulty'] ?? 'medium')));
        $estimatedTime = absint($input['estimated_time'] ?? 30);

        if (! in_array($guideType, self::SUPPORTED_GUIDE_TYPES, true)) {
            $guideType = 'jak_zrobic';
        }

        if ($topic === '') {
            $topic = 'temat poradnika';
        }

        if ($estimatedTime < 1) {
            $estimatedTime = 30;
        }

        $workflowDraft = \Poradnik\Platform\Domain\Guide\GuideGenerationWorkflow::run([
            'topic' => $topic,
            'guide_type' => $guideType,
            'difficulty' => $difficulty,
            'estimated_time' => $estimatedTime,
        ]);

        $title = sanitize_text_field((string) ($workflowDraft['title'] ?? self::buildTitle($guideType, $topic)));

        $intro = trim(sanitize_textarea_field((string) ($input['intro'] ?? '')));
        if ($intro === '') {
            $intro = trim(sanitize_textarea_field((string) ($workflowDraft['intro'] ?? self::buildIntro($topic))));
        }

        $steps = self::normalizeSteps($input['steps'] ?? []);
        if ($steps === []) {
            $steps = self::normalizeSteps($workflowDraft['steps'] ?? []);
        }
        if ($steps === []) {
            $steps = self::defaultSteps($topic);
        }

        $tools = self::normalizeTextList($input['tools'] ?? []);
        if ($tools === []) {
            $tools = self::normalizeTextList($workflowDraft['tools'] ?? []);
        }

        $faq = self::normalizeFaq($input['faq'] ?? []);
        if ($faq === []) {
            $faq = self::normalizeFaq($workflowDraft['faq'] ?? []);
        }
        if ($faq === []) {
            $faq = self::defaultFaq($topic);
        }

        $metaDescription = trim(sanitize_text_field((string) ($workflowDraft['meta_description'] ?? '')));
        if ($metaDescription === '') {
            $metaDescription = self::buildMetaDescription($topic);
        }

        return [
            'title' => $title,
            'intro' => $intro,
            'guide_type' => $guideType,
            'difficulty' => $difficulty === '' ? 'medium' : $difficulty,
            'estimated_time' => $estimatedTime,
            'tools' => $tools,
            'steps' => $steps,
            'faq' => $faq,
            'meta_description' => $metaDescription,
            'workflow' => is_array($workflowDraft['workflow'] ?? null) ? $workflowDraft['workflow'] : [],
        ];
    }

    /**
     * @param array<string, mixed> $draft
     * @param int $authorId
     * @return int|WP_Error
     */
    public static function saveDraft(array $draft, int $authorId = 0)
    {
        $title = sanitize_text_field((string) ($draft['title'] ?? 'Poradnik'));
        $content = self::buildPostContent($draft);

        $postData = [
            'post_type' => 'guide',
            'post_status' => 'draft',
            'post_title' => $title,
            'post_content' => $content,
        ];

        if ($authorId > 0) {
            $postData['post_author'] = $authorId;
        }

        $postId = wp_insert_post($postData, true);

        if (is_wp_error($postId) || ! is_int($postId) || $postId < 1) {
            return is_wp_error($postId)
                ? $postId
                : new WP_Error('poradnik_guide_generate_insert_failed', 'Cannot insert guide draft.', ['status' => 500]);
        }

        update_post_meta($postId, 'guide_type', sanitize_key((string) ($draft['guide_type'] ?? 'jak_zrobic')));
        update_post_meta($postId, 'difficulty', sanitize_text_field((string) ($draft['difficulty'] ?? 'medium')));
        update_post_meta($postId, 'estimated_time', absint($draft['estimated_time'] ?? 30));
        update_post_meta($postId, 'tools_needed', implode("\n", self::normalizeTextList($draft['tools'] ?? [])));
        update_post_meta($postId, 'steps', self::stepsToAcf($draft['steps'] ?? []));
        update_post_meta($postId, 'faq_items', self::faqToAcf($draft['faq'] ?? []));
        update_post_meta($postId, '_poradnik_generated_by', 'guide_generator');

        return $postId;
    }

    /**
     * @param mixed $items
     * @return array<int, string>
     */
    private static function normalizeTextList($items): array
    {
        if (! is_array($items)) {
            return [];
        }

        $result = [];
        foreach ($items as $item) {
            $value = trim(sanitize_text_field((string) $item));
            if ($value !== '') {
                $result[] = $value;
            }

            if (count($result) >= 20) {
                break;
            }
        }

        return $result;
    }

    /**
     * @param mixed $steps
     * @return array<int, array<string, mixed>>
     */
    private static function normalizeSteps($steps): array
    {
        if (! is_array($steps)) {
            return [];
        }

        $result = [];
        foreach ($steps as $index => $step) {
            if (! is_array($step)) {
                continue;
            }

            $title = trim(sanitize_text_field((string) ($step['title'] ?? $step['step_title'] ?? '')));
            $description = trim(sanitize_textarea_field((string) ($step['description'] ?? $step['step_description'] ?? '')));
            $tip = trim(sanitize_textarea_field((string) ($step['tip'] ?? $step['step_tip'] ?? '')));

            if ($title === '' && $description === '') {
                continue;
            }

            $result[] = [
                'title' => $title,
                'description' => $description,
                'tip' => $tip,
                'order' => absint($step['order'] ?? $step['step_order'] ?? ($index + 1)),
            ];

            if (count($result) >= 20) {
                break;
            }
        }

        usort(
            $result,
            static fn (array $left, array $right): int => ((int) ($left['order'] ?? 0)) <=> ((int) ($right['order'] ?? 0))
        );

        return $result;
    }

    /**
     * @param mixed $faq
     * @return array<int, array<string, string>>
     */
    private static function normalizeFaq($faq): array
    {
        if (! is_array($faq)) {
            return [];
        }

        $result = [];
        foreach ($faq as $row) {
            if (! is_array($row)) {
                continue;
            }

            $question = trim(sanitize_text_field((string) ($row['question'] ?? '')));
            $answer = trim(sanitize_textarea_field((string) ($row['answer'] ?? '')));

            if ($question === '' || $answer === '') {
                continue;
            }

            $result[] = [
                'question' => $question,
                'answer' => $answer,
            ];

            if (count($result) >= 12) {
                break;
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function defaultSteps(string $topic): array
    {
        return [
            ['title' => 'Przygotowanie', 'description' => 'Przygotuj wszystko, czego potrzebujesz do realizacji zadania: ' . $topic . '.', 'tip' => 'Sprawdź warunki wstępne.', 'order' => 1],
            ['title' => 'Konfiguracja', 'description' => 'Skonfiguruj podstawowe ustawienia i narzędzia potrzebne do wykonania.', 'tip' => 'Pracuj według checklisty.', 'order' => 2],
            ['title' => 'Wykonanie kroków', 'description' => 'Wykonaj kolejne działania krok po kroku i kontroluj rezultat.', 'tip' => 'Po każdym kroku zweryfikuj efekt.', 'order' => 3],
            ['title' => 'Test i korekta', 'description' => 'Przetestuj wynik i popraw ewentualne błędy lub niedociągnięcia.', 'tip' => 'Zapisz ustawienia po poprawkach.', 'order' => 4],
            ['title' => 'Finalizacja', 'description' => 'Zamknij proces, podsumuj rezultat i przygotuj krótką listę dalszych działań.', 'tip' => 'Dodaj notatkę z kluczowymi wnioskami.', 'order' => 5],
        ];
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function defaultFaq(string $topic): array
    {
        $raw = (string) (ContentGenerator::generate('faq', $topic)['output'] ?? '');
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        $faq = [];
        $question = '';
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (str_starts_with($trimmed, 'Q:')) {
                $question = trim(substr($trimmed, 2));
                continue;
            }

            if (str_starts_with($trimmed, 'A:') && $question !== '') {
                $answer = trim(substr($trimmed, 2));
                if ($answer !== '') {
                    $faq[] = ['question' => $question, 'answer' => $answer];
                }

                $question = '';
            }

            if (count($faq) >= 6) {
                break;
            }
        }

        if ($faq !== []) {
            return $faq;
        }

        return [
            ['question' => 'Od czego zacząć: ' . $topic . '?', 'answer' => 'Zacznij od przygotowania narzędzi i planu kroków.'],
            ['question' => 'Ile czasu to zajmie?', 'answer' => 'Zależy od poziomu zaawansowania i złożoności tematu, zwykle 20-90 minut.'],
        ];
    }

    private static function buildTitle(string $guideType, string $topic): string
    {
        $topic = trim($topic);

        return match ($guideType) {
            'jak_ustawic' => 'Jak ustawić ' . $topic,
            'jak_wymienic' => 'Jak wymienić ' . $topic,
            'jak_naprawic' => 'Jak naprawić ' . $topic,
            'jak_skonfigurowac' => 'Jak skonfigurować ' . $topic,
            'jak_wyczyscic' => 'Jak wyczyścić ' . $topic,
            'jak_zainstalowac' => 'Jak zainstalować ' . $topic,
            default => 'Jak zrobić ' . $topic,
        };
    }

    private static function buildIntro(string $topic): string
    {
        return 'W tym poradniku krok po kroku pokażemy, jak skutecznie wykonać: ' . $topic . '. Znajdziesz tu praktyczne wskazówki, kolejność działań i najczęstsze błędy.';
    }

    private static function buildMetaDescription(string $topic): string
    {
        $meta = (string) (ContentGenerator::generate('meta', $topic)['output'] ?? '');
        $meta = trim(sanitize_text_field($meta));

        if ($meta === '') {
            $meta = 'Jak wykonać ' . $topic . ' krok po kroku. Sprawdź narzędzia, kolejność działań i praktyczne wskazówki.';
        }

        return mb_substr($meta, 0, 155);
    }

    /**
     * @param mixed $steps
     * @return array<int, array<string, mixed>>
     */
    private static function stepsToAcf($steps): array
    {
        $normalized = self::normalizeSteps($steps);
        $rows = [];

        foreach ($normalized as $row) {
            $rows[] = [
                'step_title' => (string) ($row['title'] ?? ''),
                'step_description' => (string) ($row['description'] ?? ''),
                'step_tip' => (string) ($row['tip'] ?? ''),
                'step_order' => absint($row['order'] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @param mixed $faq
     * @return array<int, array<string, string>>
     */
    private static function faqToAcf($faq): array
    {
        return self::normalizeFaq($faq);
    }

    /**
     * @param array<string, mixed> $draft
     */
    private static function buildPostContent(array $draft): string
    {
        $parts = [];

        $intro = trim((string) ($draft['intro'] ?? ''));
        if ($intro !== '') {
            $parts[] = '<p>' . esc_html($intro) . '</p>';
        }

        $steps = self::normalizeSteps($draft['steps'] ?? []);
        foreach ($steps as $index => $step) {
            $title = trim((string) ($step['title'] ?? 'Krok ' . ($index + 1)));
            $description = trim((string) ($step['description'] ?? ''));
            $tip = trim((string) ($step['tip'] ?? ''));

            $parts[] = '<h2>' . esc_html($title) . '</h2>';
            if ($description !== '') {
                $parts[] = '<p>' . esc_html($description) . '</p>';
            }
            if ($tip !== '') {
                $parts[] = '<p><strong>Tip:</strong> ' . esc_html($tip) . '</p>';
            }
        }

        $faq = self::normalizeFaq($draft['faq'] ?? []);
        if ($faq !== []) {
            $parts[] = '<h2>FAQ</h2>';

            foreach ($faq as $row) {
                $question = trim((string) ($row['question'] ?? ''));
                $answer = trim((string) ($row['answer'] ?? ''));

                if ($question === '' || $answer === '') {
                    continue;
                }

                $parts[] = '<h3>' . esc_html($question) . '</h3>';
                $parts[] = '<p>' . esc_html($answer) . '</p>';
            }
        }

        return implode("\n\n", $parts);
    }
}
