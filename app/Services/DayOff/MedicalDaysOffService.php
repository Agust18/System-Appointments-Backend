<?php
namespace App\Services\DayOff;

use App\Exception\DayOff\DayOffException;
use App\Models\DayOff\DayOffModel;
use DateTime;

/**
 * Única fuente de "¿este médico atiende ese día?" en cuanto a bloqueos (de la clínica o del médico).
 * Lo usan el booking (dentro del lock), el validador de horario y la disponibilidad.
 */
final class MedicalDaysOffService
{
    private DayOffModel $dayOffModel;

    public function __construct()
    {
        $this->dayOffModel = new DayOffModel();
    }

    public function isDayOff(int $medicalId, string $fecha): bool
    {
        return $this->dayOffModel->existsForMedicalOnDate($medicalId, $fecha);
    }

    /**
     * @throws DayOffException si la fecha está bloqueada
     */
    public function assertNotDayOff(int $medicalId, string $fecha): void
    {
        if ($this->isDayOff($medicalId, $fecha)) {
            throw new DayOffException();
        }
    }

    /**
     * Fechas bloqueadas dentro de [$from, $to], en una sola query.
     *
     * @return array<string, true> fecha Y-m-d => true
     */
    public function findDayOffDates(int $medicalId, string $from, string $to): array
    {
        $dates = [];

        foreach ($this->dayOffModel->findByMedicalAndRange($medicalId, $from, $to) as $dayOff) {
            $current = new DateTime(max($dayOff->getDateFrom(), $from));
            $end = new DateTime(min($dayOff->getDateTo(), $to));

            while ($current <= $end) {
                $dates[$current->format('Y-m-d')] = true;
                $current->modify('+1 day');
            }
        }

        return $dates;
    }
}
