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
        $rating = (float) self::acfOrMeta($postId, 'rating');
        if ($rating <= 0) {
            $rating = (float) get_post_meta($postId, 'review_rating', true);
        }

        $verdict = (string) self::acfOrMeta($postId, 'verdict');
        if ($verdict === '') {
            $verdict = (string) get_post_meta($postId, 'review_verdict', true);
        }

        $pros = self::acfOrMeta($postId, 'pros');
        if ($pros === '' || $pros === [] || $pros === null) {
            $pros = get_post_meta($postId, 'review_pros', true);
        }

        $cons = self::acfOrMeta($postId, 'cons');
        if ($cons === '' || $cons === [] || $cons === null) {
            $cons = get_post_meta($postId, 'review_cons', true);
        }

        $specs = self::normalizeSpecs(self::acfOrMeta($postId, 'specification_table'));
        $affiliateLink = esc_url_raw((string) self::acfOrMeta($postId, 'affiliate_link'));

        return [
            'rating' => max(0, min(5, $rating > 0 ? $rating : 4.5)),
            'verdict' => $verdict !== '' ? $verdict : 'Solidny wybor dla wiekszosci uzytkownikow.',
            'pros' => self::normalizeList($pros, ['Latwe wdrozenie', 'Dobry stosunek ceny do jakosci']),
            'cons' => self::normalizeList($cons, ['Niektore funkcje wymagaja konfiguracji']),
            'specs' => $specs !== [] ? $specs : self::defaultSpecs(),
            'affiliate_link' => $affiliateLink,
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
        $affiliateLink = isset($review['affiliate_link']) ? esc_url((string) $review['affiliate_link']) : '';

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

        if ($affiliateLink !== '') {
            $html .= '<p><a class="button" href="' . $affiliateLink . '" rel="nofollow sponsored noopener" target="_blank">Sprawdz oferte</a></p>';
        }

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
        if (is_string($value) && $value !== '') {
            $value = preg_split('/\r\n|\r|\n|,/', $value) ?: [];
        }

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

    /**
     * @return array<int, array{label:string,value:string}>
     */
    private static function normalizeSpecs($value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (! is_array($item)) {
                continue;
            }

            $label = trim(wp_strip_all_tags((string) ($item['label'] ?? '')));
            $specValue = trim(wp_strip_all_tags((string) ($item['value'] ?? '')));
            if ($label === '' || $specValue === '') {
                continue;
            }

            $items[] = ['label' => $label, 'value' => $specValue];
        }

        return $items;
    }

    /**
     * @return array<int, array{label:string,value:string}>
     */
    private static function defaultSpecs(): array
    {
        return [
            ['label' => 'Model wdrozenia', 'value' => 'SaaS'],
            ['label' => 'Wsparcie', 'value' => 'e-mail + baza wiedzy'],
            ['label' => 'Integracje', 'value' => 'API + webhook'],
        ];
    }

    private static function acfOrMeta(int $postId, string $fieldName)
    {
        if (function_exists('get_field')) {
            $value = get_field($fieldName, $postId);
            if ($value !== null && $value !== false && $value !== '') {
                return $value;
            }
        }

        return get_post_meta($postId, $fieldName, true);
    }
}
