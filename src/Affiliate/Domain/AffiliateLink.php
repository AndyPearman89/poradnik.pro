<?php

namespace Poradnik\AfilacjaAdsense\Affiliate\Domain;

class AffiliateLink
{
    private int $id;
    private string $title;
    private string $slug;
    private string $destinationUrl;
    private string $category;
    private string $description;
    private string $buttonText;
    private string $imageUrl;
    private int $clicks;

    public function __construct(array $data)
    {
        $this->id = (int) ($data['id'] ?? 0);
        $this->title = (string) ($data['title'] ?? '');
        $this->slug = (string) ($data['slug'] ?? '');
        $this->destinationUrl = (string) ($data['destination_url'] ?? '');
        $this->category = (string) ($data['category'] ?? '');
        $this->description = (string) ($data['description'] ?? '');
        $this->buttonText = (string) ($data['button_text'] ?? 'Sprawdź ofertę');
        $this->imageUrl = (string) ($data['image_url'] ?? '');
        $this->clicks = (int) ($data['clicks'] ?? 0);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'destination_url' => $this->destinationUrl,
            'category' => $this->category,
            'description' => $this->description,
            'button_text' => $this->buttonText,
            'image_url' => $this->imageUrl,
            'clicks' => $this->clicks,
        ];
    }
}
