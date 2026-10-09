<?php

namespace App\Exception\Platform;

use Exception;

class PlatformAdminEmailAlreadyExistsException extends Exception
{
    public function __construct()
    {
        parent::__construct("Ya existe un administrador de plataforma con ese email", 409);
    }
}
