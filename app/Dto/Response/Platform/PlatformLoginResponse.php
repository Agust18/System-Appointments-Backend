<?php

namespace App\Dto\Response\Platform;

final readonly class PlatformLoginResponse
{
    public function __construct(
        public string $token,
        public PlatformAdminResponse $admin,
    ) {}
}
