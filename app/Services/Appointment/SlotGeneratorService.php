<?php
namespace App\Services\Appointment;

final class SlotGeneratorService
{
    /**
     * Genera los slots disponibles de UN día a partir de sus rangos horarios
     * activos y los turnos ya existentes ese día. Sin acceso a DB.
     *
     * @param \App\Entity\MedicalSchedule\MedicalSchedule[] $activeRanges
     * @param array $existingAppointments stdClass{hora, hora_fin} del día
     * @return array<array{start_time: string, end_time: string, block_end_time: string}>
     */
    public function generate(
        array $activeRanges,
        array $existingAppointments,
        int $effectiveDuration,
        int $totalBlockMinutes,
        int $slotStepMinutes
    ): array {
        $availableSlots = [];

        //recorre cada rango de atención
        foreach ($activeRanges as $range) {
            $rangeStartMinutes = $this->timeToMinutes(substr($range->getStartTime(), 0, 5));
            $rangeEndMinutes = $this->timeToMinutes(substr($range->getEndTime(), 0, 5));

            for ($candidateStart = $rangeStartMinutes; $candidateStart <= ($rangeEndMinutes - $totalBlockMinutes); $candidateStart += $slotStepMinutes) {
                $candidateEnd = $candidateStart + $totalBlockMinutes;       // Con buffer (bloque total)
                $visibleEnd = $candidateStart + $effectiveDuration;        // Sin buffer (visible para paciente)

                if ($candidateEnd > $rangeEndMinutes) {
                    continue;
                }

                $candidateStartStr = $this->minutesToTime($candidateStart) . ':00';
                $candidateEndStr = $this->minutesToTime($candidateEnd) . ':00';

                //chequea solapamiento: candidate_start < turno.hora_fin AND candidate_end > turno.hora
                $overlaps = false;
                foreach ($existingAppointments as $appt) {
                    $apptHora = substr($appt->hora, 0, 8);
                    $apptHoraFin = substr($appt->hora_fin, 0, 8);

                    if ($candidateStartStr < $apptHoraFin && $candidateEndStr > $apptHora) {
                        $overlaps = true;
                        break;
                    }
                }

                if (!$overlaps) {
                    $availableSlots[] = [
                        'start_time'     => substr($candidateStartStr, 0, 5),
                        'end_time'       => $this->minutesToTime($visibleEnd),       //duración real sin buffer
                        'block_end_time' => substr($candidateEndStr, 0, 5)          //fin con buffer (bloqueo total)
                    ];
                }
            }
        }

        return $availableSlots;
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time);
        return ((int)$hours * 60) + (int)$minutes;
    }

    private function minutesToTime(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
