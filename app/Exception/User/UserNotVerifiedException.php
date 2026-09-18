<?php
namespace App\Exception\User;

use Exception;

final class UserNotVerifiedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Debés verificar tu email antes de iniciar sesión.', 403);
    }
}