<?php

namespace PearTree\ProgrammaticAffiliate\Affiliate\Application;

use PearTree\ProgrammaticAffiliate\Affiliate\Infrastructure\AffiliateRepository;

class AutoLinkEngine
{
    private AffiliateRepository $repository;

    public function __construct(AffiliateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function register(): void
    {
        add_filter('the_content', [$this, 'injectLinks'], 20);
    }

    public function injectLinks(string $content): string
    {
        if (is_admin() || trim($content) === '') {
            return $content;
        }

        $settings = get_option('ppae_general_settings', ['autolink_enabled' => 1]);
        if (empty($settings['autolink_enabled'])) {
            return $content;
        }

        $keywords = $this->repository->getKeywordsWithProducts();
        if (empty($keywords)) {
            return $content;
        }

        foreach ($keywords as $keywordRow) {
            $keyword = trim((string) ($keywordRow['keyword'] ?? ''));
            $slug = sanitize_title((string) ($keywordRow['product_slug'] ?? ''));
            if ($keyword === '' || $slug === '') {
                continue;
            }

            $url = esc_url(home_url('/go/' . $slug));
            $escapedKeyword = preg_quote($keyword, '/');
            $replacement = '<a href="' . $url . '" rel="sponsored nofollow">$1</a>';

            $newContent = preg_replace('/\b(' . $escapedKeyword . ')\b/i', $replacement, $content, 1);
            if (is_string($newContent) && $newContent !== $content) {
                $content = $newContent;
            }
        }

        return $content;
    }
}
