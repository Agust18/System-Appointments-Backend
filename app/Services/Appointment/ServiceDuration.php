<?php
namespace App\Services\Appointment;

final readonly class ServiceDuration
{
    public function __construct(
        private int $effectiveDurationMinutes,
        private int $totalBlockMinutes
    ) {
    }

    public function getEffectiveDurationMinutes(): int
    {
        return $this->effectiveDurationMinutes;
    }

    public function getTotalBlockMinutes(): int
    {
        return $this->totalBlockMinutes;
    }
}
