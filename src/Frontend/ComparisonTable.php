<?php

namespace PearTree\ProgrammaticAffiliate\Frontend;

class ComparisonTable
{
    public function render(array $products): string
    {
        ob_start();
        include PPAE_PATH . 'templates/comparison-table.php';
        return (string) ob_get_clean();
    }
}
