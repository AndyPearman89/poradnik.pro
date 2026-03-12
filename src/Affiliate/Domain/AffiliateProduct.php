<?php

namespace PearTree\ProgrammaticAffiliate\Affiliate\Domain;

class AffiliateProduct
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
