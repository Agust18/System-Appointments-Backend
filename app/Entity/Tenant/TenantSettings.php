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
        private ?DateTime $createdAt,
        private ?DateTime $updatedAt
    ) {}

    /**
     * Configuración inicial de un Tenant recién creado: el nombre visible parte del nombre del
     * Tenant, `frontend_url` es el que se pide en el alta y todo lo demás queda sin configurar.
     */
    public static function defaultsFor(Tenant $tenant, ?string $frontendUrl = null): self
    {
        return new self($tenant->getId(), $tenant->getName(), null, $frontendUrl, null, true, new DateTime(), new DateTime());
    }

    /**
     * Aplica solo los campos presentes en el request (ya validado por TenantSettingsUpdateValidatorService).
     */
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

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }
}
