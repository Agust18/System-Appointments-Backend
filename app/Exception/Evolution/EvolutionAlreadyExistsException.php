<?php
namespace App\Exception\Evolution;

use Exception;

class EvolutionAlreadyExistsException extends Exception
{
    public function __construct(int $appointmentId)
    {
        parent::__construct("El turno {$appointmentId} ya tiene una evolución. Para modificarla, corregila con una nueva.", 409);
    }
}
