<?php

namespace App\Services\Platform\Tenant;

use App\Exception\Auth\InvalidPasswordResetTokenException;
use App\Services\Auth\AbstractUserTokenService;

/**
 * Token del link de bienvenida del primer ADMIN. Es del tipo `password_reset` para que
 * /reset-password lo acepte sin cambios, pero dura 72 horas: el ADMIN puede abrir el email días
 * después del alta (el reset normal dura 1 hora).
 */
final class TenantAdminWelcomeTokenService extends AbstractUserTokenService
{
    public const TTL_HOURS = 72;

    protected function getType(): string
    {
        return 'password_reset';
    }

    protected function getTtl(): string
    {
        return '+' . self::TTL_HOURS . ' hours';
    }

    protected function getInvalidTokenException(): \Throwable
    {
        return new InvalidPasswordResetTokenException();
    }
}
