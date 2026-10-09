<?php

namespace App\Exception\Tenant;

use Exception;

class TenantNotFoundException extends Exception
{
    public function __construct()
    {
        parent::__construct("No se encontro el cliente", 404);
    }
}
