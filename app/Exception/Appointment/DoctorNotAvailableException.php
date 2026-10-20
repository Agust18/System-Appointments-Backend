<?php
namespace App\Exception\Appointment;

use Exception;

/**
 * El médico no atiende en el día o la franja horaria pedidos.
 */
class DoctorNotAvailableException extends Exception
{
    private const DAY_NAMES = [
        1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves',
        5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo',
    ];

    public function __construct(string $message)
    {
        parent::__construct($message, 400);
    }

    public static function onDay(string $doctorName, int $dayOfWeek): self
    {
        $dayName = self::DAY_NAMES[$dayOfWeek] ?? 'Día desconocido';

        return new self("El médico {$doctorName} no atiende los {$dayName}");
    }

    public static function outsideHours(string $doctorName, string $startTime, string $endTime): self
    {
        return new self("El médico {$doctorName} solo atiende de {$startTime} a {$endTime}");
    }
}
