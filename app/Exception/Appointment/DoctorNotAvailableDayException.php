<?php
namespace App\Exception\Appointment;

use Exception;

class DoctorNotAvailableDayException extends Exception
{
    public function __construct(string $doctorName, int $dayOfWeek)
    {
        $dayName = $this->getDayName($dayOfWeek);
        parent::__construct("El médico {$doctorName} no atiende los {$dayName}", 400);
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