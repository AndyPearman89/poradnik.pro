<?php

namespace Poradnik\Platform\Modules\ContentModel;

use Poradnik\Platform\Core\EventLogger;
use Poradnik\Platform\Domain\Content\FieldGroups;

if (! defined('ABSPATH')) {
    exit;
}

final class Module
{
    /**
     * @var array<string, string>
     */
    private const POST_TYPES = [
        'guide' => 'poradnik',
        'ranking' => 'ranking',
        'review' => 'recenzja',
        'comparison' => 'porownanie',
        'news' => 'news',
        'tool' => 'narzedzie',
        'sponsored' => 'sponsorowany',
    ];

    /**
     * @var array<string, string>
     */
    private const TAXONOMIES = [
        'topic' => 'temat',
        'intent' => 'intencja',
        'stage' => 'etap',
        'industry' => 'branza',
        'difficulty' => 'trudnosc',
        'device' => 'urzadzenie',
        'software' => 'oprogramowanie-tax',
        'product_category' => 'kategoria-produktu',
    ];

    public static function init(): void
    {
        FieldGroups::init();
        add_action('init', [self::class, 'register'], 5);
    }

    public static function register(): void
    {
        self::registerPostTypes();
        self::registerTaxonomies();

        EventLogger::dispatch(
            'poradnik_platform_content_model_registered',
            [
                'post_types' => array_keys(self::POST_TYPES),
                'taxonomies' => array_keys(self::TAXONOMIES),
            ]
        );
    }

    private static function registerPostTypes(): void
    {
        foreach (self::POST_TYPES as $postType => $slug) {
            register_post_type(
                $postType,
                [
                    'labels' => self::postTypeLabels($postType),
                    'public' => true,
                    'show_ui' => true,
                    'show_in_rest' => true,
                    'has_archive' => true,
                    'menu_position' => 20,
                    'rewrite' => [
                        'slug' => $slug,
                        'with_front' => false,
                    ],
                    'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions'],
                ]
            );
        }
    }

    private static function registerTaxonomies(): void
    {
        $objectTypes = array_keys(self::POST_TYPES);

        foreach (self::TAXONOMIES as $taxonomy => $slug) {
            register_taxonomy(
                $taxonomy,
                $objectTypes,
                [
                    'labels' => self::taxonomyLabels($taxonomy),
                    'public' => true,
                    'show_ui' => true,
                    'show_in_rest' => true,
                    'hierarchical' => true,
                    'rewrite' => [
                        'slug' => $slug,
                        'with_front' => false,
                    ],
                ]
            );
        }
    }

    /**
     * @return array<string, string>
     */
    private static function postTypeLabels(string $postType): array
    {
        $singular = self::postTypeSingularLabel($postType);
        $plural = self::postTypePluralLabel($postType);

        return [
            'name' => $plural,
            'singular_name' => $singular,
            'add_new_item' => 'Dodaj nowy: ' . $singular,
            'edit_item' => 'Edytuj: ' . $singular,
            'new_item' => 'Nowy: ' . $singular,
            'view_item' => 'Zobacz: ' . $singular,
            'search_items' => 'Szukaj: ' . $plural,
            'not_found' => 'Brak wpisow dla typu: ' . $plural,
            'all_items' => $plural,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function taxonomyLabels(string $taxonomy): array
    {
        $singular = self::taxonomySingularLabel($taxonomy);
        $plural = self::taxonomyPluralLabel($taxonomy);

        return [
            'name' => $plural,
            'singular_name' => $singular,
            'search_items' => 'Szukaj: ' . $plural,
            'all_items' => $plural,
            'edit_item' => 'Edytuj: ' . $singular,
            'update_item' => 'Aktualizuj: ' . $singular,
            'add_new_item' => 'Dodaj: ' . $singular,
            'new_item_name' => 'Nowa nazwa: ' . $singular,
        ];
    }

    private static function postTypeSingularLabel(string $postType): string
    {
        return match ($postType) {
            'guide' => 'Poradnik',
            'ranking' => 'Ranking',
            'review' => 'Recenzja',
            'comparison' => 'Porownanie',
            'news' => 'Aktualnosc',
            'tool' => 'Narzedzie',
            'sponsored' => 'Artykul sponsorowany',
            default => ucfirst($postType),
        };
    }

    private static function postTypePluralLabel(string $postType): string
    {
        return match ($postType) {
            'guide' => 'Poradniki',
            'ranking' => 'Rankingi',
            'review' => 'Recenzje',
            'comparison' => 'Porownania',
            'news' => 'Aktualnosci',
            'tool' => 'Narzedzia',
            'sponsored' => 'Artykuly sponsorowane',
            default => ucfirst($postType),
        };
    }

    private static function taxonomySingularLabel(string $taxonomy): string
    {
        return match ($taxonomy) {
            'topic' => 'Temat',
            'intent' => 'Intencja',
            'stage' => 'Etap',
            'industry' => 'Branza',
            'difficulty' => 'Trudnosc',
            'device' => 'Urzadzenie',
            'software' => 'Oprogramowanie',
            'product_category' => 'Kategoria produktu',
            default => ucfirst($taxonomy),
        };
    }

    private static function taxonomyPluralLabel(string $taxonomy): string
    {
        return match ($taxonomy) {
            'topic' => 'Tematy',
            'intent' => 'Intencje',
            'stage' => 'Etapy',
            'industry' => 'Branze',
            'difficulty' => 'Poziomy trudnosci',
            'device' => 'Urzadzenia',
            'software' => 'Oprogramowanie',
            'product_category' => 'Kategorie produktow',
            default => ucfirst($taxonomy),
        };
    }
}
