<?php

namespace App\Exception\Platform;

use Exception;

class TenantSlugAlreadyExistsException extends Exception
{
    public function __construct()
    {
        parent::__construct("Ya existe un cliente con ese identificador", 409);
    }
}
