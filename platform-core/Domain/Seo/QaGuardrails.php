<?php

namespace Poradnik\Platform\Domain\Seo;

use WP_Error;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Quality guardrails for programmatic SEO drafts before auto-publication.
 *
 * Checks:
 *  1. Title not empty, min 10 chars, max 100 chars.
 *  2. Content not empty, min 150 words.
 *  3. Title does not appear in the banned-claims list.
 *  4. No placeholder tokens left (e.g. "[produkt]", "{{topic}}").
 *  5. Content has at least one <h2>.
 */
final class QaGuardrails
{
    private const MIN_WORDS     = 150;
    private const MIN_TITLE_LEN = 10;
    private const MAX_TITLE_LEN = 100;

    /**
     * Banned claim patterns (case-insensitive regex fragments).
     *
     * @var array<int, string>
     */
    private const BANNED_CLAIMS = [
        'guaranteed',
        'gwarantowany',
        'no 1 na swiecie',
        '#1 na swiecie',
        'najlepszy na swiecie',
        '\bfake\b',
        'za darmo bez rejestracji',
    ];

    /**
     * @return true|WP_Error
     */
    public static function check(int $postId)
    {
        $post = get_post($postId);
        if (! $post instanceof \WP_Post) {
            return new WP_Error('qa_not_found', "Post {$postId} not found.", ['status' => 404]);
        }

        $title   = (string) $post->post_title;
        $content = wp_strip_all_tags((string) $post->post_content);

        $errors = [];

        // 1. Title length.
        $titleLen = mb_strlen(trim($title));
        if ($titleLen < self::MIN_TITLE_LEN) {
            $errors[] = "Title too short ({$titleLen} chars, min " . self::MIN_TITLE_LEN . ').';
        }
        if ($titleLen > self::MAX_TITLE_LEN) {
            $errors[] = "Title too long ({$titleLen} chars, max " . self::MAX_TITLE_LEN . ').';
        }

        // 2. Word count.
        $wordCount = str_word_count(strip_tags($post->post_content));
        if ($wordCount < self::MIN_WORDS) {
            $errors[] = "Content too short ({$wordCount} words, min " . self::MIN_WORDS . ').';
        }

        // 3. Banned claims.
        $checkText = mb_strtolower($title . ' ' . $content);
        foreach (self::BANNED_CLAIMS as $claim) {
            if (preg_match('/' . $claim . '/ui', $checkText)) {
                $errors[] = "Banned claim detected: \"{$claim}\".";
            }
        }

        // 4. Unresolved placeholder tokens.
        $placeholders = [];
        preg_match_all('/\[[\w\s]+\]|\{\{[\w\s]+\}\}/u', $title . ' ' . $content, $placeholders);
        if (! empty($placeholders[0])) {
            $found = implode(', ', array_unique($placeholders[0]));
            $errors[] = "Unresolved placeholder tokens: {$found}.";
        }

        // 5. At least one <h2> in content.
        if (stripos($post->post_content, '<h2') === false) {
            $errors[] = 'Content has no <h2> headings.';
        }

        if ($errors !== []) {
            return new WP_Error(
                'qa_failed',
                implode(' | ', $errors),
                ['status' => 422, 'post_id' => $postId, 'issues' => $errors]
            );
        }

        return true;
    }
}
