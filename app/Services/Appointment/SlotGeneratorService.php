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
        int $totalBlockMinutes
    ): array {
        $busyBlocks = $this->toMinuteBlocks($existingAppointments);
        $availableSlots = [];

        foreach ($activeRanges as $range) {
            $rangeStart = $this->timeToMinutes($range->getStartTime());
            $rangeEnd = $this->timeToMinutes($range->getEndTime());

            for ($blockStart = $rangeStart; $blockStart + $totalBlockMinutes <= $rangeEnd; $blockStart += $totalBlockMinutes) {
                $blockEnd = $blockStart + $totalBlockMinutes;

                if ($this->overlapsAny($blockStart, $blockEnd, $busyBlocks)) {
                    continue;
                }

                $availableSlots[] = [
                    'start_time' => $this->minutesToTime($blockStart),
                    'end_time' => $this->minutesToTime($blockStart + $effectiveDuration), // duración real, sin buffer
                    'block_end_time' => $this->minutesToTime($blockEnd),                  // fin con buffer
                ];
            }
        }

        return $availableSlots;
    }

    /**
     * @param array $appointments stdClass{hora, hora_fin}
     * @return array<array{0: int, 1: int}> [inicio, fin] en minutos
     */
    private function toMinuteBlocks(array $appointments): array
    {
        $blocks = [];
        foreach ($appointments as $appointment) {
            $blocks[] = [$this->timeToMinutes($appointment->hora), $this->timeToMinutes($appointment->hora_fin)];
        }

        return $blocks;
    }

    /**
     * Hay solapamiento si el bloque empieza antes de que termine el turno
     * y termina después de que empiece.
     *
     * @param array<array{0: int, 1: int}> $busyBlocks
     */
    private function overlapsAny(int $blockStart, int $blockEnd, array $busyBlocks): bool
    {
        foreach ($busyBlocks as [$busyStart, $busyEnd]) {
            if ($blockStart < $busyEnd && $blockEnd > $busyStart) {
                return true;
            }
        }

        return false;
    }

    private function timeToMinutes(string $time): int
    {
        [$hours, $minutes] = explode(':', $time); // ignora los segundos si vienen
        return ((int) $hours * 60) + (int) $minutes;
    }

    private function minutesToTime(int $totalMinutes): string
    {
        $hours = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
