<?php

namespace Poradnik\Platform\Domain\Guide;

use Poradnik\Platform\Domain\Ai\ContentGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class GuideGenerationWorkflow
{
    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    public static function run(array $input): array
    {
        $topic = trim(sanitize_text_field((string) ($input['topic'] ?? 'temat poradnika')));
        $guideType = sanitize_key((string) ($input['guide_type'] ?? 'jak_zrobic'));
        $difficulty = trim(sanitize_text_field((string) ($input['difficulty'] ?? 'medium')));
        $estimatedTime = max(1, absint($input['estimated_time'] ?? 30));

        if ($topic === '') {
            $topic = 'temat poradnika';
        }

        $analysis = self::analyzeType($guideType, $topic);
        $structure = self::generateAiStructure($topic);
        $steps = self::stepsFromStructure($topic, $structure);
        $faq = self::faqFromAi($topic);

        $draft = [
            'title' => self::titleFromAiOrFallback($topic, $guideType),
            'intro' => self::introFromAnalysis($analysis),
            'guide_type' => $guideType,
            'difficulty' => $difficulty === '' ? 'medium' : $difficulty,
            'estimated_time' => $estimatedTime,
            'tools' => self::toolsForType($guideType),
            'steps' => $steps,
            'faq' => $faq,
            'meta_description' => self::metaFromAiOrFallback($topic),
            'workflow' => [
                'analysis' => $analysis,
                'structure' => $structure,
                'redaction' => [
                    'status' => 'applied',
                    'notes' => [
                        'Ujednolicono styl tytułów kroków.',
                        'Skrócono opisy do formy instrukcyjnej.',
                        'Zachowano układ gotowy pod publikację draft.',
                    ],
                ],
            ],
        ];

        return self::redactDraft($draft);
    }

    /**
     * @return array<string, string>
     */
    private static function analyzeType(string $guideType, string $topic): array
    {
        $verb = match ($guideType) {
            'jak_ustawic' => 'ustawić',
            'jak_wymienic' => 'wymienić',
            'jak_naprawic' => 'naprawić',
            'jak_skonfigurowac' => 'skonfigurować',
            'jak_wyczyscic' => 'wyczyścić',
            'jak_zainstalowac' => 'zainstalować',
            default => 'zrobić',
        };

        return [
            'intent' => $guideType,
            'target_action' => $verb,
            'topic' => $topic,
        ];
    }

    /**
     * @return array<int, string>
     */
    private static function generateAiStructure(string $topic): array
    {
        $outlineRaw = (string) (ContentGenerator::generate('outline', $topic)['output'] ?? '');
        $lines = preg_split('/\r\n|\r|\n/', $outlineRaw) ?: [];

        $sections = [];
        foreach ($lines as $line) {
            $value = trim((string) $line);
            if ($value === '') {
                continue;
            }

            if (str_starts_with($value, 'H2:')) {
                $value = trim(substr($value, 3));
            } elseif (str_starts_with($value, 'H1:')) {
                continue;
            }

            if ($value !== '') {
                $sections[] = $value;
            }

            if (count($sections) >= 8) {
                break;
            }
        }

        if ($sections === []) {
            $sections = ['Przygotowanie', 'Konfiguracja', 'Wdrożenie', 'Testy', 'Podsumowanie'];
        }

        return $sections;
    }

    /**
     * @param array<int, string> $structure
     * @return array<int, array<string, mixed>>
     */
    private static function stepsFromStructure(string $topic, array $structure): array
    {
        $steps = [];

        foreach ($structure as $index => $section) {
            $title = trim(sanitize_text_field($section));
            if ($title === '') {
                continue;
            }

            $position = $index + 1;
            $steps[] = [
                'title' => $title,
                'description' => 'Wykonaj etap: ' . $title . ' dla tematu „' . $topic . '” i sprawdź wynik przed przejściem dalej.',
                'tip' => 'Krok ' . $position . ': potwierdź rezultat i zapisz ustawienia.',
                'order' => $position,
            ];

            if (count($steps) >= 10) {
                break;
            }
        }

        return $steps;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private static function faqFromAi(string $topic): array
    {
        $raw = (string) (ContentGenerator::generate('faq', $topic)['output'] ?? '');
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        $faq = [];
        $question = '';

        foreach ($lines as $line) {
            $value = trim((string) $line);
            if ($value === '') {
                continue;
            }

            if (str_starts_with($value, 'Q:')) {
                $question = trim(substr($value, 2));
                continue;
            }

            if (str_starts_with($value, 'A:') && $question !== '') {
                $answer = trim(substr($value, 2));
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
            ['question' => 'Jak zacząć z tematem: ' . $topic . '?', 'answer' => 'Zacznij od przygotowania i wykonuj kroki po kolei.'],
            ['question' => 'Jak uniknąć błędów?', 'answer' => 'Po każdym kroku wykonuj szybką weryfikację i rób kopię ustawień.'],
        ];
    }

    private static function titleFromAiOrFallback(string $topic, string $guideType): string
    {
        $raw = (string) (ContentGenerator::generate('headline', $topic)['output'] ?? '');
        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];

        foreach ($lines as $line) {
            $candidate = trim(preg_replace('/^\d+\)\s*/', '', (string) $line));
            if ($candidate !== '') {
                return sanitize_text_field($candidate);
            }
        }

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

    /**
     * @param array<string, string> $analysis
     */
    private static function introFromAnalysis(array $analysis): string
    {
        $action = trim((string) ($analysis['target_action'] ?? 'wykonać'));
        $topic = trim((string) ($analysis['topic'] ?? 'zadanie'));

        return 'Ten poradnik pokazuje krok po kroku, jak ' . $action . ' „' . $topic . '”, z naciskiem na praktyczne wdrożenie i szybką weryfikację efektów.';
    }

    /**
     * @return array<int, string>
     */
    private static function toolsForType(string $guideType): array
    {
        $base = ['Lista kroków', 'Notatnik'];

        $extra = match ($guideType) {
            'jak_naprawic' => ['Narzędzie diagnostyczne'],
            'jak_zainstalowac' => ['Instalator / pakiet'],
            'jak_skonfigurowac' => ['Panel ustawień'],
            default => ['Przeglądarka internetowa'],
        };

        return array_values(array_unique(array_merge($base, $extra)));
    }

    private static function metaFromAiOrFallback(string $topic): string
    {
        $meta = (string) (ContentGenerator::generate('meta', $topic)['output'] ?? '');
        $meta = trim(sanitize_text_field($meta));

        if ($meta === '') {
            $meta = 'Jak wykonać ' . $topic . ' krok po kroku. Sprawdź narzędzia, działania i najczęstsze problemy.';
        }

        return mb_substr($meta, 0, 155);
    }

    /**
     * @param array<string, mixed> $draft
     * @return array<string, mixed>
     */
    private static function redactDraft(array $draft): array
    {
        $draft['title'] = trim(sanitize_text_field((string) ($draft['title'] ?? 'Poradnik')));
        $draft['intro'] = trim(sanitize_textarea_field((string) ($draft['intro'] ?? '')));
        $draft['meta_description'] = mb_substr(trim(sanitize_text_field((string) ($draft['meta_description'] ?? ''))), 0, 155);

        if ($draft['title'] === '') {
            $draft['title'] = 'Poradnik';
        }

        if ($draft['intro'] === '') {
            $draft['intro'] = 'Poradnik krok po kroku.';
        }

        return $draft;
    }
}
