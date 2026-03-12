<?php

namespace Poradnik\AfilacjaAdsense\Frontend;

class AffiliateBox
{
    public function render(array $affiliate): string
    {
        $template = PAA_PATH . 'templates/affiliate-box.php';
        ob_start();
        include $template;
        return (string) ob_get_clean();
    }
}
