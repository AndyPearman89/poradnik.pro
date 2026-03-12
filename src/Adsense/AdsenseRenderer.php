<?php

namespace PearTree\ProgrammaticAffiliate\Adsense;

class AdsenseRenderer
{
    private AdsenseManager $manager;

    public function __construct(AdsenseManager $manager)
    {
        $this->manager = $manager;
    }

    public function render(string $placement): string
    {
        $placements = ['header', 'sidebar', 'article_top', 'article_middle', 'article_bottom', 'footer'];
        if (!in_array($placement, $placements, true)) {
            return '';
        }

        $settings = $this->manager->getSettings();
        $publisherId = (string) ($settings['publisher_id'] ?? '');
        $script = trim((string) ($settings['script'] ?? ''));
        $autoAds = !empty($settings['auto_ads']);

        if ($publisherId === '' && $script === '') {
            return '';
        }

        $slot = apply_filters('ppae_adsense_slot_' . $placement, '0000000000', $placement);

        ob_start();
        include PPAE_PATH . 'templates/adsense-banner.php';
        return (string) ob_get_clean();
    }
}
