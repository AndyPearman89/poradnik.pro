<?php

namespace PearTree\ProgrammaticAffiliate\Frontend;

class RankingList
{
    public function render(array $products): string
    {
        ob_start();
        include PPAE_PATH . 'templates/ranking-list.php';
        return (string) ob_get_clean();
    }
}
