<?php
namespace App\Services\Appointment;

final readonly class ServiceDuration
{
    public function __construct(
        private int $effectiveDurationMinutes,
        private int $bufferMinutes,
        private int $totalBlockMinutes
    ) {
    }

    public function getEffectiveDurationMinutes(): int
    {
        return $this->effectiveDurationMinutes;
    }

    public function getBufferMinutes(): int
    {
        return $this->bufferMinutes;
    }

    public function getTotalBlockMinutes(): int
    {
        return $this->totalBlockMinutes;
    }
}
