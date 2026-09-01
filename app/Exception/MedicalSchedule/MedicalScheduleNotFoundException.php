<?php

namespace App\exception\MedicalSchedule;

use Exception;

class MedicalScheduleNotFoundException extends Exception
{
   

    public function __construct(int $id)
    {
        
        parent::__construct("No se encontro el horario con ID : " . $id, 404);
    }
}
