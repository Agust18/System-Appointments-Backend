<?php
namespace App\Exception\Evolution;

use Exception;

class EvolutionAccessDeniedException extends Exception
{
    public function __construct(string $message = 'No tenés permiso para acceder a las evoluciones de este paciente.')
    {
        parent::__construct($message, 403);
    }
}
