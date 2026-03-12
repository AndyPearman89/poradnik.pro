<?php

namespace Poradnik\AfilacjaAdsense\Adsense;

class AdsenseRenderer
{
    private AdsenseManager $manager;

    public function __construct(AdsenseManager $manager)
    {
        $this->manager = $manager;
    }

    public function render(string $placement): string
    {
        $allowed = ['header', 'sidebar', 'article_top', 'article_middle', 'article_bottom', 'footer'];
        if (!in_array($placement, $allowed, true)) {
            return '';
        }

        $settings = $this->manager->getSettings();
        $publisher = (string) $settings['publisher_id'];
        $manualScript = trim((string) $settings['adsense_script']);

        if ($publisher === '' && $manualScript === '') {
            return '';
        }

        $slot = apply_filters('paa_adsense_slot_' . $placement, '0000000000', $placement);
        $template = PAA_PATH . 'templates/adsense-banner.php';

        ob_start();
        include $template;
        return (string) ob_get_clean();
    }
}
