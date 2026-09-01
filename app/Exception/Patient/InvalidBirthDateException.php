<?php
namespace App\Exception\Patient;

use Exception;

class InvalidBirthDateException extends Exception
{
    public function __construct()
    {
        parent::__construct("la fecha no puede ser futura", 400);
    }
}