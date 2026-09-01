<?php
namespace App\Exception\MedicalSchedule;

use Exception;

class MedicalNotActiveException extends Exception
{
    public function __construct(int $medicalId)
    {
        parent::__construct("El médico con ID {$medicalId} no está activo", 400);
    }
}