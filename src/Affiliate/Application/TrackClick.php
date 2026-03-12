<?php

namespace Poradnik\AfilacjaAdsense\Affiliate\Application;

use Poradnik\AfilacjaAdsense\Affiliate\Infrastructure\AffiliateRepository;

class TrackClick
{
    private AffiliateRepository $repository;

    public function __construct(AffiliateRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(int $affiliateId): void
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) wp_unslash($_SERVER['REMOTE_ADDR']) : '';
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? (string) wp_unslash($_SERVER['HTTP_USER_AGENT']) : '';
        $referrer = isset($_SERVER['HTTP_REFERER']) ? (string) wp_unslash($_SERVER['HTTP_REFERER']) : '';

        $this->repository->trackClick($affiliateId, $ip, $userAgent, $referrer);
    }
}
