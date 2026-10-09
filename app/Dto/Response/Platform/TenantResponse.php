<?php

namespace App\Dto\Response\Platform;

final readonly class TenantResponse
{
    public function __construct(
        public int $id,
        public string $slug,
        public string $name,
        public string $status,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}
}
