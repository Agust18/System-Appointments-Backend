<?php
namespace App\Exception\DayOff;

use Exception;

class DayOffNotFoundException extends Exception
{
    public function __construct(int $id)
    {
        parent::__construct("No se encontró el día no laborable con ID : " . $id, 404);
    }
}
