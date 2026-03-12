<?php

namespace Poradnik\Platform\Domain\Review;

if (! defined('ABSPATH')) {
    exit;
}

final class ReviewService
{
    /**
     * @return array<string, mixed>
     */
    public static function fromPost(int $postId): array
    {
        $rating = (float) get_post_meta($postId, 'review_rating', true);
        $verdict = (string) get_post_meta($postId, 'review_verdict', true);
        $pros = get_post_meta($postId, 'review_pros', true);
        $cons = get_post_meta($postId, 'review_cons', true);

        return [
            'rating' => max(0, min(5, $rating > 0 ? $rating : 4.5)),
            'verdict' => $verdict !== '' ? $verdict : 'Solidny wybor dla wiekszosci uzytkownikow.',
            'pros' => self::normalizeList($pros, ['Latwe wdrozenie', 'Dobry stosunek ceny do jakosci']),
            'cons' => self::normalizeList($cons, ['Niektore funkcje wymagaja konfiguracji']),
        ];
    }

    /**
     * @param array<string, mixed> $review
     */
    public static function renderBox(array $review): string
    {
        $rating = number_format((float) ($review['rating'] ?? 0), 1);
        $verdict = esc_html((string) ($review['verdict'] ?? ''));

        $pros = is_array($review['pros'] ?? null) ? $review['pros'] : [];
        $cons = is_array($review['cons'] ?? null) ? $review['cons'] : [];

        $html = '<div class="poradnik-review-box">';
        $html .= '<p><strong>Ocena:</strong> ' . esc_html((string) $rating) . '/5</p>';
        $html .= '<p><strong>Werdykt:</strong> ' . $verdict . '</p>';

        $html .= '<div class="poradnik-review-columns">';
        $html .= '<div><strong>Plusy</strong><ul>';
        foreach ($pros as $item) {
            $html .= '<li>' . esc_html((string) $item) . '</li>';
        }
        $html .= '</ul></div>';

        $html .= '<div><strong>Minusy</strong><ul>';
        foreach ($cons as $item) {
            $html .= '<li>' . esc_html((string) $item) . '</li>';
        }
        $html .= '</ul></div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    /**
     * @param mixed $value
     * @param array<int, string> $default
     * @return array<int, string>
     */
    private static function normalizeList($value, array $default): array
    {
        if (! is_array($value) || $value === []) {
            return $default;
        }

        $items = [];
        foreach ($value as $item) {
            $text = trim(wp_strip_all_tags((string) $item));
            if ($text !== '') {
                $items[] = $text;
            }
        }

        return $items !== [] ? $items : $default;
    }
}
