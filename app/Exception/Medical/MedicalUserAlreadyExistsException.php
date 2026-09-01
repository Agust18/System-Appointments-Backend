<?php
namespace App\Exception\Medical;

use Exception;

class MedicalUserAlreadyExistsException extends Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("El usuario con ID {$userId} ya tiene un perfil médico", 409);
    }
}