<?php

namespace PearTree\ProgrammaticAffiliate\SEO\Application;

use PearTree\ProgrammaticAffiliate\SEO\Infrastructure\SeoPageRepository;

class SeoPageGenerator
{
    private SeoPageRepository $repository;

    public function __construct(SeoPageRepository $repository)
    {
        $this->repository = $repository;
    }

    public function generate(array $payload): int
    {
        $keyword = sanitize_text_field((string) ($payload['keyword'] ?? ''));
        $slug = sanitize_title((string) ($payload['slug'] ?? $keyword));
        $title = sanitize_text_field((string) ($payload['title'] ?? ('Best ' . $keyword)));
        $category = sanitize_text_field((string) ($payload['category'] ?? 'seo-affiliate'));
        $intro = sanitize_textarea_field((string) ($payload['content_template'] ?? ''));

        $existing = get_page_by_path($slug, OBJECT, 'page');
        if ($existing instanceof \WP_Post) {
            $pageId = (int) $existing->ID;
        } else {
            $content = '[peartree_seo_page slug="' . $slug . '"]';
            $pageId = wp_insert_post([
                'post_type' => 'page',
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => $slug,
                'post_content' => $content,
            ]);
        }

        if ($pageId > 0) {
            update_post_meta($pageId, '_ppae_meta_title', $title);
            update_post_meta($pageId, '_ppae_meta_description', 'Porównanie, ranking i rekomendacje dla: ' . $keyword);
        }

        return $this->repository->insert([
            'keyword' => $keyword,
            'slug' => $slug,
            'title' => $title,
            'content_template' => $intro,
            'category' => $category,
            'wp_page_id' => $pageId,
        ]);
    }
}
