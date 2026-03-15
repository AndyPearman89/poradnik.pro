<?php

namespace Poradnik\Platform\Domain\Tenant;

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Represents a single marketplace portal tenant (multisite instance).
 */
final class Tenant
{
    public readonly int    $id;
    public readonly string $slug;
    public readonly string $name;
    public readonly string $domain;
    public readonly string $status;
    public readonly string $plan;
    public readonly int    $owner_id;
    public readonly string $created_at;
    public readonly string $updated_at;

    /**
     * @param array<string, mixed> $data
     */
    public function __construct(array $data)
    {
        $this->id         = (int)    ($data['id']         ?? 0);
        $this->slug       = (string) ($data['slug']       ?? '');
        $this->name       = (string) ($data['name']       ?? '');
        $this->domain     = (string) ($data['domain']     ?? '');
        $this->status     = (string) ($data['status']     ?? 'pending');
        $this->plan       = (string) ($data['plan']       ?? 'free');
        $this->owner_id   = (int)    ($data['owner_id']   ?? 0);
        $this->created_at = (string) ($data['created_at'] ?? '');
        $this->updated_at = (string) ($data['updated_at'] ?? '');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'slug'       => $this->slug,
            'name'       => $this->name,
            'domain'     => $this->domain,
            'status'     => $this->status,
            'plan'       => $this->plan,
            'owner_id'   => $this->owner_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
