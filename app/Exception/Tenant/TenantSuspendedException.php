<?php

namespace App\Exception\Tenant;

use Exception;

class TenantSuspendedException extends Exception
{
    public function __construct()
    {
        parent::__construct("El cliente se encuentra suspendido", 403);
    }
}
