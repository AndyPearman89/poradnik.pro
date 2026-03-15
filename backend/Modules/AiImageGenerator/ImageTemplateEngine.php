<?php

namespace Poradnik\Platform\Modules\AiImageGenerator;

if (! defined('ABSPATH')) {
    exit;
}

final class ImageTemplateEngine
{
    /**
     * @return array<string, string>
     */
    public static function styles(): array
    {
        return [
            'guide' => 'simple illustration, instruction icons, step style',
            'ranking' => 'product cards, comparison layout, tech ranking style',
            'review' => 'product spotlight, rating stars, clean background',
            'news' => 'headline illustration, minimal graphic',
            'comparison' => 'split design, product vs product',
        ];
    }

    public static function styleForType(string $type): string
    {
        $type = sanitize_key($type);
        $styles = self::styles();

        return $styles[$type] ?? $styles['guide'];
    }

    /**
     * @return array<string, string>
     */
    public static function colorMap(): array
    {
        return [
            'guide' => '#6b4eff',
            'ranking' => '#ff7a18',
            'review' => '#00a8ff',
            'news' => '#2ecc71',
            'comparison' => '#e74c3c',
        ];
    }

    public static function colorForType(string $type): string
    {
        $type = sanitize_key($type);
        $colors = self::colorMap();

        return $colors[$type] ?? $colors['guide'];
    }

    /**
     * @return array<string, array{width:int,height:int,key:string}>
     */
    public static function variants(): array
    {
        return [
            'featured' => ['width' => 1200, 'height' => 630, 'key' => 'featured_image'],
            'hero' => ['width' => 1280, 'height' => 720, 'key' => 'hero_image'],
            'social' => ['width' => 1080, 'height' => 1080, 'key' => 'social_image'],
            'pinterest' => ['width' => 1000, 'height' => 1500, 'key' => 'pinterest_image'],
            'thumbnail' => ['width' => 400, 'height' => 400, 'key' => 'thumbnail_image'],
            'og' => ['width' => 1200, 'height' => 630, 'key' => 'og_image'],
        ];
    }
}
