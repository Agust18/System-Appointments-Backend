<?php
namespace App\Exception\Evolution;

use Exception;

class EvolutionNotFoundException extends Exception
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontró la evolución con ID : " . $id, 404);
    }
}
