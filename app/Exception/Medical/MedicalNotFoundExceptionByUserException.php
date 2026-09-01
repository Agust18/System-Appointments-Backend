<?php
namespace App\Exception\Medical;

use Exception;

class MedicalNotFoundExceptionByUserException extends Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("No se encontró el médico con ID de usuario: {$userId}", 404);
    }
}