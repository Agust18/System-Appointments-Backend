<?php
namespace App\Exception\Auth;

use Exception;

final class InvalidVerificationTokenException extends Exception
{
    public function __construct(string $message = 'El link de verificación no es válido.')
    {
        parent::__construct($message, 400);
    }
}