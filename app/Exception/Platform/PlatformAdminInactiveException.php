<?php

namespace App\Exception\Platform;

use Exception;

class PlatformAdminInactiveException extends Exception
{
    public function __construct()
    {
        parent::__construct("El administrador de plataforma no está activo", 403);
    }
}
