<?php
namespace App\Exception\Evolution;

use Exception;

class EvolutionAlreadySignedException extends Exception
{
    public function __construct(int $id)
    {
        parent::__construct("La evolución {$id} ya está firmada y no se puede modificar. Creá una corrección.", 409);
    }
}
