<?php

namespace Poradnik\AfilacjaAdsense\Adsense;

class AdsenseManager
{
    public const OPTION_KEY = 'paa_adsense_settings';

    public function registerSettings(): void
    {
        register_setting('paa_adsense_group', self::OPTION_KEY, [
            'type' => 'array',
            'sanitize_callback' => [$this, 'sanitizeSettings'],
            'default' => $this->defaults(),
        ]);
    }

    public function defaults(): array
    {
        return [
            'publisher_id' => '',
            'adsense_script' => '',
            'auto_ads' => 0,
        ];
    }

    public function getSettings(): array
    {
        $settings = get_option(self::OPTION_KEY, []);
        return wp_parse_args(is_array($settings) ? $settings : [], $this->defaults());
    }

    public function sanitizeSettings(array $input): array
    {
        return [
            'publisher_id' => sanitize_text_field((string) ($input['publisher_id'] ?? '')),
            'adsense_script' => wp_kses((string) ($input['adsense_script'] ?? ''), [
                'script' => [
                    'async' => true,
                    'src' => true,
                    'crossorigin' => true,
                    'data-ad-client' => true,
                ],
                'ins' => [
                    'class' => true,
                    'style' => true,
                    'data-ad-client' => true,
                    'data-ad-slot' => true,
                    'data-ad-format' => true,
                    'data-full-width-responsive' => true,
                ],
            ]),
            'auto_ads' => empty($input['auto_ads']) ? 0 : 1,
        ];
    }
}
