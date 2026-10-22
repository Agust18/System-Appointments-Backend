<?php

namespace App\Dto\Response\Tenant;


final readonly class TenantConfigResponse
{
    public function __construct(
        public string $slug,
        public string $name,
        public bool $landingEnabled,
        public TenantContactResponse $contact,
        public ?string $heroTitle,
        public ?string $heroSubtitle,
    ) {}
}
