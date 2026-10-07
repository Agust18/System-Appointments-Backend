<?php
namespace App\Entity\Tenant;

use App\Constants\TenantStatus;
use DateTime;

final class Tenant
{
    public function __construct(
        private ?int $id,
        private string $slug,
        private string $name,
        private string $status,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {}

    public function isSuspended(): bool
    {
        return $this->status === TenantStatus::SUSPENDED;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}
