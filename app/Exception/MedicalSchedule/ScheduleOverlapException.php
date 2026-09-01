<?php
namespace App\Exception\MedicalSchedule;

use Exception;

class ScheduleOverlapException extends Exception
{
    public function __construct(string $medicalName, int $dayOfWeek)
    {
        $dayName = $this->getDayName($dayOfWeek);
        parent::__construct("El médico {$medicalName} ya tiene un horario que se superpone para el día {$dayName}", 409);
    }
    
    private function getDayName(int $dayOfWeek): string
    {
        $days = [
            1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
            5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'
        ];
        return $days[$dayOfWeek] ?? 'Día desconocido';
    }
}