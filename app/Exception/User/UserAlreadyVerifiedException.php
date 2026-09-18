<?php
namespace App\Exception\User;

use Exception;

final class UserAlreadyVerifiedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Tu cuenta ya está verificada.', 200);
    }
}