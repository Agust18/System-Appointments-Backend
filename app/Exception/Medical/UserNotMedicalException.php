<?php
namespace App\Exception\User;

use Exception;

class UserNotMedicalException extends Exception
{
    public function __construct(int $userId)
    {
        parent::__construct("El usuario con ID {$userId} no tiene rol médico", 400);
    }
}