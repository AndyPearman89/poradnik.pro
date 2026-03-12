<?php

namespace Poradnik\Platform\Domain\Ranking;

if (! defined('ABSPATH')) {
    exit;
}

final class Builder
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public static function renderTable(array $items, string $ctaLabel = 'Sprawdz'): string
    {
        if ($items === []) {
            return '';
        }

        $ranked = ScoringService::rank($items);

        $html = '<div class="poradnik-ranking-builder"><table class="poradnik-ranking-table"><thead><tr><th>#</th><th>Produkt</th><th>Score</th><th>Akcja</th></tr></thead><tbody>';

        foreach ($ranked as $item) {
            $name = esc_html((string) ($item['name'] ?? 'Produkt'));
            $score = number_format((float) ($item['score'] ?? 0), 2);
            $position = (int) ($item['position'] ?? 0);
            $url = esc_url((string) ($item['url'] ?? '#'));

            $html .= '<tr>';
            $html .= '<td>' . esc_html((string) $position) . '</td>';
            $html .= '<td>' . $name . '</td>';
            $html .= '<td>' . esc_html((string) $score) . '</td>';
            $html .= '<td><a href="' . $url . '" rel="nofollow sponsored noopener" target="_blank">' . esc_html($ctaLabel) . '</a></td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }
}
