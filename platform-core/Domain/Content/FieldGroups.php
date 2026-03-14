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
                        'key' => 'field_poradnik_intro_summary',
                        'label' => 'Intro Summary',
                        'name' => 'intro_summary',
                        'type' => 'textarea',
                        'rows' => 3,
                        'instructions' => 'Short SEO summary shown near the top of the article and used for meta description fallback.',
                    ],
                    [
                        'key' => 'field_poradnik_key_takeaways',
                        'label' => 'Key Takeaways',
                        'name' => 'key_takeaways',
                        'type' => 'textarea',
                        'rows' => 4,
                        'instructions' => 'One item per line or comma-separated list.',
                    ],
                    [
                        'key' => 'field_poradnik_quick_answer_box',
                        'label' => 'Quick Answer Box',
                        'name' => 'quick_answer_box',
                        'type' => 'textarea',
                        'rows' => 3,
                        'instructions' => 'Direct answer block for featured snippets and quick summary.',
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

        acf_add_local_field_group(
            [
                'key' => 'group_poradnik_guide_fields',
                'title' => 'Poradnik Guide Fields',
                'fields' => [
                    [
                        'key' => 'field_poradnik_guide_type',
                        'label' => 'Guide Type',
                        'name' => 'guide_type',
                        'type' => 'select',
                        'choices' => [
                            'how_to' => 'How-To',
                            'tutorial' => 'Tutorial',
                            'checklist' => 'Checklist',
                            'setup' => 'Setup',
                            'troubleshooting' => 'Troubleshooting',
                        ],
                        'default_value' => 'how_to',
                        'ui' => 1,
                        'return_format' => 'value',
                    ],
                    [
                        'key' => 'field_poradnik_guide_difficulty',
                        'label' => 'Difficulty',
                        'name' => 'difficulty',
                        'type' => 'taxonomy',
                        'taxonomy' => 'difficulty',
                        'field_type' => 'select',
                        'return_format' => 'id',
                        'add_term' => 1,
                        'load_terms' => 1,
                        'save_terms' => 1,
                    ],
                    [
                        'key' => 'field_poradnik_estimated_time',
                        'label' => 'Estimated Time',
                        'name' => 'estimated_time',
                        'type' => 'number',
                        'instructions' => 'Estimated completion time in minutes.',
                        'min' => 1,
                        'step' => 1,
                    ],
                    [
                        'key' => 'field_poradnik_tools_needed',
                        'label' => 'Tools Needed',
                        'name' => 'tools_needed',
                        'type' => 'textarea',
                        'rows' => 4,
                        'instructions' => 'One tool per line or a comma-separated list.',
                    ],
                    [
                        'key' => 'field_poradnik_steps',
                        'label' => 'Steps',
                        'name' => 'steps',
                        'type' => 'repeater',
                        'layout' => 'block',
                        'button_label' => 'Add step',
                        'sub_fields' => [
                            [
                                'key' => 'field_poradnik_step_title',
                                'label' => 'Step Title',
                                'name' => 'step_title',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_poradnik_step_description',
                                'label' => 'Step Description',
                                'name' => 'step_description',
                                'type' => 'textarea',
                                'rows' => 4,
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_poradnik_step_image',
                                'label' => 'Step Image',
                                'name' => 'step_image',
                                'type' => 'image',
                                'return_format' => 'id',
                                'preview_size' => 'medium',
                                'library' => 'all',
                            ],
                            [
                                'key' => 'field_poradnik_step_tip',
                                'label' => 'Step Tip',
                                'name' => 'step_tip',
                                'type' => 'textarea',
                                'rows' => 2,
                            ],
                            [
                                'key' => 'field_poradnik_step_warning',
                                'label' => 'Step Warning',
                                'name' => 'step_warning',
                                'type' => 'textarea',
                                'rows' => 2,
                            ],
                            [
                                'key' => 'field_poradnik_step_order',
                                'label' => 'Step Order',
                                'name' => 'step_order',
                                'type' => 'number',
                                'min' => 1,
                                'step' => 1,
                            ],
                        ],
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
                ],
                'position' => 'normal',
                'style' => 'default',
                'active' => true,
            ]
        );

        acf_add_local_field_group(
            [
                'key' => 'group_poradnik_ranking_fields',
                'title' => 'Poradnik Ranking Fields',
                'fields' => [
                    [
                        'key' => 'field_poradnik_ranking_products',
                        'label' => 'Ranking Products',
                        'name' => 'ranking_products',
                        'type' => 'repeater',
                        'layout' => 'block',
                        'button_label' => 'Add ranked product',
                        'sub_fields' => [
                            [
                                'key' => 'field_poradnik_ranking_product_name',
                                'label' => 'Product Name',
                                'name' => 'product_name',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_poradnik_ranking_product_description',
                                'label' => 'Product Description',
                                'name' => 'product_description',
                                'type' => 'textarea',
                                'rows' => 3,
                            ],
                            [
                                'key' => 'field_poradnik_product_rating',
                                'label' => 'Product Rating',
                                'name' => 'product_rating',
                                'type' => 'number',
                                'min' => 0,
                                'max' => 10,
                                'step' => 0.1,
                            ],
                            [
                                'key' => 'field_poradnik_ranking_pros',
                                'label' => 'Pros',
                                'name' => 'pros',
                                'type' => 'textarea',
                                'rows' => 3,
                                'instructions' => 'One item per line or comma-separated list.',
                            ],
                            [
                                'key' => 'field_poradnik_ranking_cons',
                                'label' => 'Cons',
                                'name' => 'cons',
                                'type' => 'textarea',
                                'rows' => 3,
                                'instructions' => 'One item per line or comma-separated list.',
                            ],
                            [
                                'key' => 'field_poradnik_price_range',
                                'label' => 'Price Range',
                                'name' => 'price_range',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'field_poradnik_affiliate_link',
                                'label' => 'Affiliate Link',
                                'name' => 'affiliate_link',
                                'type' => 'url',
                            ],
                        ],
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'ranking',
                        ],
                    ],
                ],
                'position' => 'normal',
                'style' => 'default',
                'active' => true,
            ]
        );

        acf_add_local_field_group(
            [
                'key' => 'group_poradnik_review_fields',
                'title' => 'Poradnik Review Fields',
                'fields' => [
                    [
                        'key' => 'field_poradnik_review_rating',
                        'label' => 'Rating',
                        'name' => 'rating',
                        'type' => 'number',
                        'min' => 0,
                        'max' => 5,
                        'step' => 0.1,
                    ],
                    [
                        'key' => 'field_poradnik_review_pros',
                        'label' => 'Pros',
                        'name' => 'pros',
                        'type' => 'textarea',
                        'rows' => 3,
                        'instructions' => 'One item per line or comma-separated list.',
                    ],
                    [
                        'key' => 'field_poradnik_review_cons',
                        'label' => 'Cons',
                        'name' => 'cons',
                        'type' => 'textarea',
                        'rows' => 3,
                        'instructions' => 'One item per line or comma-separated list.',
                    ],
                    [
                        'key' => 'field_poradnik_review_verdict',
                        'label' => 'Verdict',
                        'name' => 'verdict',
                        'type' => 'textarea',
                        'rows' => 3,
                    ],
                    [
                        'key' => 'field_poradnik_review_specification_table',
                        'label' => 'Specification Table',
                        'name' => 'specification_table',
                        'type' => 'repeater',
                        'layout' => 'table',
                        'button_label' => 'Add specification',
                        'sub_fields' => [
                            [
                                'key' => 'field_poradnik_review_spec_label',
                                'label' => 'Label',
                                'name' => 'label',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_poradnik_review_spec_value',
                                'label' => 'Value',
                                'name' => 'value',
                                'type' => 'text',
                                'required' => 1,
                            ],
                        ],
                    ],
                    [
                        'key' => 'field_poradnik_review_affiliate_link',
                        'label' => 'Affiliate Link',
                        'name' => 'affiliate_link',
                        'type' => 'url',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'review',
                        ],
                    ],
                ],
                'position' => 'normal',
                'style' => 'default',
                'active' => true,
            ]
        );

        acf_add_local_field_group(
            [
                'key' => 'group_poradnik_comparison_fields',
                'title' => 'Poradnik Comparison Fields',
                'fields' => [
                    [
                        'key' => 'field_poradnik_comparison_product_a',
                        'label' => 'Product A',
                        'name' => 'product_a',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_poradnik_comparison_product_b',
                        'label' => 'Product B',
                        'name' => 'product_b',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_poradnik_comparison_table',
                        'label' => 'Comparison Table',
                        'name' => 'comparison_table',
                        'type' => 'repeater',
                        'layout' => 'table',
                        'button_label' => 'Add row',
                        'sub_fields' => [
                            [
                                'key' => 'field_poradnik_comparison_feature',
                                'label' => 'Feature',
                                'name' => 'feature',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_poradnik_comparison_value_a',
                                'label' => 'Value A',
                                'name' => 'value_a',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_poradnik_comparison_value_b',
                                'label' => 'Value B',
                                'name' => 'value_b',
                                'type' => 'text',
                                'required' => 1,
                            ],
                        ],
                    ],
                    [
                        'key' => 'field_poradnik_comparison_winner',
                        'label' => 'Winner',
                        'name' => 'winner',
                        'type' => 'textarea',
                        'rows' => 3,
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'comparison',
                        ],
                    ],
                ],
                'position' => 'normal',
                'style' => 'default',
                'active' => true,
            ]
        );

        acf_add_local_field_group(
            [
                'key' => 'group_poradnik_tool_fields',
                'title' => 'Poradnik Tool Fields',
                'fields' => [
                    [
                        'key' => 'field_poradnik_tool_logo',
                        'label' => 'Tool Logo',
                        'name' => 'tool_logo',
                        'type' => 'image',
                        'return_format' => 'id',
                        'preview_size' => 'medium',
                        'library' => 'all',
                    ],
                    [
                        'key' => 'field_poradnik_tool_website',
                        'label' => 'Tool Website',
                        'name' => 'tool_website',
                        'type' => 'url',
                    ],
                    [
                        'key' => 'field_poradnik_tool_category',
                        'label' => 'Tool Category',
                        'name' => 'tool_category',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_poradnik_tool_price',
                        'label' => 'Tool Price',
                        'name' => 'tool_price',
                        'type' => 'text',
                    ],
                    [
                        'key' => 'field_poradnik_tool_affiliate_link',
                        'label' => 'Affiliate Link',
                        'name' => 'affiliate_link',
                        'type' => 'url',
                    ],
                ],
                'location' => [
                    [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'tool',
                        ],
                    ],
                ],
                'position' => 'normal',
                'style' => 'default',
                'active' => true,
            ]
        );

        acf_add_local_field_group(
            [
                'key' => 'group_poradnik_timeline_fields',
                'title' => 'Poradnik Timeline Fields',
                'fields' => [
                    [
                        'key' => 'field_poradnik_timeline_type',
                        'label' => 'Timeline Type',
                        'name' => 'timeline_type',
                        'type' => 'select',
                        'choices' => [
                            'guide_steps' => 'Guide Steps',
                            'ranking_history' => 'Ranking History',
                            'product_evolution' => 'Product Evolution',
                            'comparison_flow' => 'Comparison Flow',
                            'news_events' => 'News Events',
                        ],
                        'default_value' => 'guide_steps',
                        'ui' => 1,
                        'return_format' => 'value',
                    ],
                    [
                        'key' => 'field_poradnik_timeline_theme',
                        'label' => 'Timeline Theme',
                        'name' => 'timeline_theme',
                        'type' => 'select',
                        'choices' => [
                            'default' => 'Default',
                            'minimal' => 'Minimal',
                            'accent' => 'Accent',
                        ],
                        'default_value' => 'default',
                        'ui' => 1,
                        'return_format' => 'value',
                    ],
                    [
                        'key' => 'field_poradnik_timeline_layout',
                        'label' => 'Timeline Layout',
                        'name' => 'timeline_layout',
                        'type' => 'select',
                        'choices' => [
                            'vertical' => 'Vertical',
                            'horizontal' => 'Horizontal',
                            'progress' => 'Progress',
                            'milestone' => 'Milestone',
                        ],
                        'default_value' => 'vertical',
                        'ui' => 1,
                        'return_format' => 'value',
                    ],
                    [
                        'key' => 'field_poradnik_timeline_steps',
                        'label' => 'Timeline Steps',
                        'name' => 'timeline_steps',
                        'type' => 'repeater',
                        'layout' => 'block',
                        'button_label' => 'Add timeline step',
                        'sub_fields' => [
                            [
                                'key' => 'field_poradnik_timeline_step_title',
                                'label' => 'Step Title',
                                'name' => 'step_title',
                                'type' => 'text',
                                'required' => 1,
                            ],
                            [
                                'key' => 'field_poradnik_timeline_step_description',
                                'label' => 'Step Description',
                                'name' => 'step_description',
                                'type' => 'textarea',
                                'rows' => 3,
                            ],
                            [
                                'key' => 'field_poradnik_timeline_step_image',
                                'label' => 'Step Image',
                                'name' => 'step_image',
                                'type' => 'image',
                                'return_format' => 'id',
                                'preview_size' => 'medium',
                                'library' => 'all',
                            ],
                            [
                                'key' => 'field_poradnik_timeline_step_icon',
                                'label' => 'Step Icon',
                                'name' => 'step_icon',
                                'type' => 'text',
                            ],
                            [
                                'key' => 'field_poradnik_timeline_step_tip',
                                'label' => 'Step Tip',
                                'name' => 'step_tip',
                                'type' => 'textarea',
                                'rows' => 2,
                            ],
                            [
                                'key' => 'field_poradnik_timeline_step_warning',
                                'label' => 'Step Warning',
                                'name' => 'step_warning',
                                'type' => 'textarea',
                                'rows' => 2,
                            ],
                            [
                                'key' => 'field_poradnik_timeline_step_link',
                                'label' => 'Step Link',
                                'name' => 'step_link',
                                'type' => 'url',
                            ],
                            [
                                'key' => 'field_poradnik_timeline_step_order',
                                'label' => 'Step Order',
                                'name' => 'step_order',
                                'type' => 'number',
                                'min' => 1,
                                'step' => 1,
                            ],
                        ],
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
                            'value' => 'news',
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
