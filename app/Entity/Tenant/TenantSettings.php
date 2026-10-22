<?php
namespace App\Entity\Tenant;

use App\Dto\Request\Platform\TenantSettingsUpdateRequest;
use App\Tenancy\FrontendUrl;
use DateTime;

final class TenantSettings
{
    public function __construct(
        private int $tenantId,
        private string $name,
        private ?string $replyToEmail,
        private ?string $frontendUrl,
        private ?string $receptionEmail,
        private bool $receptionEmailEnabled,
        private ?string $contactPhone,
        private ?string $contactWhatsapp,
        private ?string $contactEmail,
        private ?string $address,
        private ?string $openingHours,
        private bool $landingEnabled,
        private ?string $heroTitle,
        private ?string $heroSubtitle,
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {}


    public static function defaultsFor(Tenant $tenant, ?string $frontendUrl = null): self
    {
        return new self(
            $tenant->getId(),
            $tenant->getName(),
            null, $frontendUrl,
            null,
            true,
            null,
            null,
            null,
            null,
            null,
            true,
            null,
            null,
            new DateTime(),
            new DateTime()
        );
    }

    public function modify(TenantSettingsUpdateRequest $request): void
    {
        if ($request->has('name')) {
            $this->name = $request->getName();
        }
        if ($request->has('replyToEmail')) {
            $this->replyToEmail = $request->getReplyToEmail();
        }
        if ($request->has('frontendUrl')) {
            $url = $request->getFrontendUrl();
            // allowHttp = true: la regla de producción ya la aplicó el validator; acá solo se normaliza.
            $this->frontendUrl = $url === null ? null : FrontendUrl::normalize($url, true);
        }
        if ($request->has('receptionEmail')) {
            $this->receptionEmail = $request->getReceptionEmail();
        }
        if ($request->has('receptionEmailEnabled')) {
            $this->receptionEmailEnabled = $request->getReceptionEmailEnabled();
        }
        if ($request->has('contactPhone')) {
            $this->contactPhone = $request->getContactPhone();
        }
        if ($request->has('contactWhatsapp')) {
            $this->contactWhatsapp = $request->getContactWhatsapp();
        }
        if ($request->has('contactEmail')) {
            $this->contactEmail = $request->getContactEmail();
        }
        if ($request->has('address')) {
            $this->address = $request->getAddress();
        }
        if ($request->has('openingHours')) {
            $this->openingHours = $request->getOpeningHours();
        }
        if ($request->has('landingEnabled')) {
            $this->landingEnabled = $request->getLandingEnabled();
        }
        if ($request->has('heroTitle')) {
            $this->heroTitle = $request->getHeroTitle();
        }
        if ($request->has('heroSubtitle')) {
            $this->heroSubtitle = $request->getHeroSubtitle();
        }

        $this->updatedAt = new DateTime();
    }

    public function getTenantId(): int
    {
        return $this->tenantId;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getReplyToEmail(): ?string
    {
        return $this->replyToEmail;
    }

    public function getFrontendUrl(): ?string
    {
        return $this->frontendUrl;
    }

    public function getReceptionEmail(): ?string
    {
        return $this->receptionEmail;
    }

    public function isReceptionEmailEnabled(): bool
    {
        return $this->receptionEmailEnabled;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function getContactWhatsapp(): ?string
    {
        return $this->contactWhatsapp;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getOpeningHours(): ?string
    {
        return $this->openingHours;
    }

    public function isLandingEnabled(): bool
    {
        return $this->landingEnabled;
    }

    public function getHeroTitle(): ?string
    {
        return $this->heroTitle;
    }

    public function getHeroSubtitle(): ?string
    {
        return $this->heroSubtitle;
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
