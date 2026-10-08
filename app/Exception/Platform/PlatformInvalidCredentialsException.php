<?php

namespace App\Exception\Platform;

use Exception;

/**
 * Mismo texto para email inexistente y contraseña incorrecta: no revela si el email existe.
 */
class PlatformInvalidCredentialsException extends Exception
{
    public function __construct()
    {
        parent::__construct("Credenciales inválidas", 401);
    }
}
