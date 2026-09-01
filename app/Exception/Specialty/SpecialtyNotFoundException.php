<?php

namespace App\exception\Specialty;

use Exception;

class SpecialtyNotFoundException extends Exception
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontro la especialidad con ID : " . $id, 404);
    }
}
