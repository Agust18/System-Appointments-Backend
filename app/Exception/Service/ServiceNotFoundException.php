<?php 

namespace App\Exception\Service;

use Exception;

class ServiceNotFoundException extends Exception
{
    public function __construct(int $id)
    {  
        parent::__construct("No se encontro el servicio con ID : " . $id, 404);
    }
}
