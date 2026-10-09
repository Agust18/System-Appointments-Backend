<?php
namespace App\Services\Appointment;

use App\Entity\MedicalService\MedicalService;
use App\Entity\Service\Service;

final class ServiceDurationCalculatorService
{
    /**
     * Duración efectiva (override del médico o base del servicio) + buffer.
     */
    public function calculate(Service $service, MedicalService $medicalService): ServiceDuration
    {
        $effectiveDuration = $medicalService->getCustomDurationMinutes() ?? $service->getDurationMinutes();
        $bufferMinutes = $service->getBufferMinutes();

        return new ServiceDuration($effectiveDuration, $bufferMinutes, $effectiveDuration + $bufferMinutes);
    }
}
