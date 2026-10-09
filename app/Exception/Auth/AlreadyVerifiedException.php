<?php
namespace App\Exception\Auth;

use Exception;

final class AlreadyVerifiedException extends Exception
{
    public function __construct()
    {
        parent::__construct('Tu cuenta ya está verificada.', 409);
    }
}