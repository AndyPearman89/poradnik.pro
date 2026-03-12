<?php

namespace PearTree\ProgrammaticAffiliate\Affiliate\Domain;

class AffiliateKeyword
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
