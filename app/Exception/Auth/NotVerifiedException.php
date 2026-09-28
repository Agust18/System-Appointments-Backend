<?php
namespace App\Exception\Auth;

use Exception;

final class NotVerifiedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Debés verificar tu email antes de iniciar sesión.', 403);
    }
}