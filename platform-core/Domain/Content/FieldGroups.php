<?php

namespace Poradnik\Platform\Domain\Content;

use Poradnik\Platform\Core\EventLogger;

if (! defined('ABSPATH')) {
    exit;
}

final class FieldGroups
{
    public static function init(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        add_action('acf/init', [self::class, 'register']);
    }

    public static function register(): void
    {
        if (! function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group(
            [
                'key' => 'group_poradnik_content_meta',
                'title' => 'Poradnik Content Meta',
                'fields' => [
                    [
                        'key' => 'field_poradnik_reading_time',
                        'label' => 'Reading Time',
                        'name' => 'reading_time',
                        'type' => 'number',
                        'instructions' => 'Estimated reading time in minutes.',
                        'min' => 1,
                        'step' => 1,
                    ],
                    [
                        'key' => 'field_poradnik_toc_enabled',
                        'label' => 'Enable TOC',
                        'name' => 'toc_enabled',
                        'type' => 'true_false',
                        'ui' => 1,
                        'default_value' => 1,
                    ],
                    [
                        'key' => 'field_poradnik_faq_items',
                        'label' => 'FAQ Items',
                        'name' => 'faq_items',
                        'type' => 'repeater',
                        'layout' => 'block',
                        'button_label' => 'Add FAQ item',
                        'sub_fields' => [
                            [
                                'key' => 'field_poradnik_faq_question',
                                'label' => 'Question',
                                'name' => 'question',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_poradnik_faq_answer',
                                'label' => 'Answer',
                                'name' => 'answer',
                                'type' => 'textarea',
                                'rows' => 4,
                                'required' => 1,
                            ],
                        ],
                    ],
                    [
                        'key' => 'field_poradnik_related_articles',
                        'label' => 'Related Articles',
                        'name' => 'related_articles',
                        'type' => 'relationship',
                        'post_type' => ['guide', 'ranking', 'review', 'comparison', 'news', 'tool', 'sponsored'],
                        'filters' => ['search', 'post_type', 'taxonomy'],
                        'return_format' => 'id',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'guide',
                        ],
                    ],
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'ranking',
                        ],
                    ],
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'review',
                        ],
                    ],
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'comparison',
                        ],
                    ],
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'news',
                        ],
                    ],
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'tool',
                        ],
                    ],
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'sponsored',
                        ],
                    ],
                ],
                'position' => 'normal',
                'style' => 'default',
                'active' => true,
            ]
        );

        EventLogger::dispatch('poradnik_platform_acf_field_groups_registered');
    }
}
