<?php
namespace App\Entity\Tenant;

use App\Constants\TenantStatus;
use App\Dto\Request\Platform\TenantRequest;
use App\Dto\Request\Platform\TenantUpdateRequest;
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
    ) {
    }

    public static function fromRequest(TenantRequest $request): self
    {
        return new self(
            null,
            $request->getSlug(),
            $request->getName(),
            TenantStatus::ACTIVE,
            new DateTime(),
            new DateTime()
        );
    }


    public function modify(TenantUpdateRequest $request): void
    {
        $this->name = $request->getName() ?? $this->name;
        $this->status = $request->getStatus() ?? $this->status;
    }

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
