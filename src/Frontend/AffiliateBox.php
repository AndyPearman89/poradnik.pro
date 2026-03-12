<?php

namespace PearTree\ProgrammaticAffiliate\Frontend;

class AffiliateBox
{
    public function render(array $product): string
    {
        ob_start();
        include PPAE_PATH . 'templates/affiliate-box.php';
        return (string) ob_get_clean();
    }
}
