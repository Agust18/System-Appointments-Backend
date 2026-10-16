<?php

namespace App\Dto\Response\Tenant;

/**
 * Contacto PÚBLICO del Tenant. `email` es el de contacto, nunca reception_email ni reply_to_email.
 */
final readonly class TenantContactResponse
{
    public function __construct(
        public ?string $phone,
        public ?string $whatsapp,
        public ?string $email,
        public ?string $address,
        public ?string $openingHours,
    ) {}
}
