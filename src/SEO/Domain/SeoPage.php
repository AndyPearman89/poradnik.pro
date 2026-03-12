<?php

namespace PearTree\ProgrammaticAffiliate\SEO\Domain;

class SeoPage
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
