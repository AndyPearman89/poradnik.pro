<?php

namespace Poradnik\Platform\Domain\Ai;

if (! defined('ABSPATH')) {
    exit;
}

final class ContentGenerator
{
    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    public static function generate(string $tool, string $input, array $options = []): array
    {
        $tool = sanitize_key($tool);
        $input = trim(wp_strip_all_tags($input));

        if ($input === '') {
            return ['tool' => $tool, 'output' => ''];
        }

        return match ($tool) {
            'headline' => ['tool' => $tool, 'output' => self::headline($input)],
            'outline' => ['tool' => $tool, 'output' => self::outline($input)],
            'faq' => ['tool' => $tool, 'output' => self::faq($input)],
            'meta' => ['tool' => $tool, 'output' => self::meta($input)],
            'ranking' => ['tool' => $tool, 'output' => self::ranking($input, $options)],
            default => ['tool' => $tool, 'output' => self::outline($input)],
        };
    }

    /**
     * @return array<int, string>
     */
    public static function tools(): array
    {
        return ['headline', 'outline', 'faq', 'meta', 'ranking'];
    }

    private static function headline(string $topic): string
    {
        return implode("\n", [
            '1) ' . strtoupper($topic) . ' - praktyczny przewodnik 2026',
            '2) Jak wybrac ' . $topic . ': checklista krok po kroku',
            '3) ' . $topic . ' - ranking opcji, ceny i plusy/minusy',
            '4) Najczestsze bledy przy wyborze: ' . $topic,
            '5) ' . $topic . ': najlepsze rozwiazania dla poczatkujacych i PRO',
        ]);
    }

    private static function outline(string $topic): string
    {
        return implode("\n", [
            'H1: ' . $topic . ' - kompletny poradnik',
            'H2: Dla kogo jest to rozwiazanie',
            'H2: Najwazniejsze kryteria wyboru',
            'H2: Krok po kroku: jak wdrozyc',
            'H2: Porownanie top opcji',
            'H2: Koszty i ROI',
            'H2: Najczestsze problemy i rozwiazania',
            'H2: FAQ',
            'H2: Podsumowanie i rekomendacja',
        ]);
    }

    private static function faq(string $topic): string
    {
        return implode("\n\n", [
            'Q: Czym jest ' . $topic . '?\nA: To rozwiazanie, ktore pomaga osiagnac lepsze wyniki szybciej i bardziej przewidywalnie.',
            'Q: Ile kosztuje wdrozenie ' . $topic . '?\nA: Koszt zalezy od skali, planu i poziomu automatyzacji, ale zwykle zaczyna sie od wersji podstawowej.',
            'Q: Kiedy pojawiaja sie pierwsze efekty?\nA: Najczesciej po 2-6 tygodniach przy regularnej pracy i optymalizacji.',
            'Q: Jakie sa najwieksze bledy?\nA: Brak strategii, zle metryki i brak testowania wariantow.',
        ]);
    }

    private static function meta(string $topic): string
    {
        $description = $topic . ' - sprawdz praktyczny przewodnik, porownanie opcji, koszty i rekomendacje. Zobacz jak wybrac najlepsze rozwiazanie krok po kroku.';

        return mb_substr($description, 0, 155);
    }

    /**
     * @param array<string, mixed> $options
     */
    private static function ranking(string $topic, array $options): string
    {
        $items = isset($options['items']) && is_array($options['items']) ? $options['items'] : [];

        if ($items === []) {
            $items = ['Opcja A', 'Opcja B', 'Opcja C'];
        }

        $lines = ['Ranking dla: ' . $topic];
        $position = 1;
        foreach ($items as $item) {
            $name = wp_strip_all_tags((string) $item);
            if ($name === '') {
                continue;
            }

            $lines[] = $position . '. ' . $name . ' - mocna opcja przy dobrym stosunku ceny do funkcji.';
            $position++;
        }

        return implode("\n", $lines);
    }
}
