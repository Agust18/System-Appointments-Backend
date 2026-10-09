<?php

namespace App\Dto\Response\Platform;

final readonly class PlatformAdminResponse
{
    public function __construct(
        public int $id,
        public string $email,
        public string $firstName,
        public string $lastName,
        public bool $isActive,
    ) {}
}
