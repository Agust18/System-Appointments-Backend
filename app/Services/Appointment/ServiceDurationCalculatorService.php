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
        $effectiveDuration = $medicalService->getCustomDurationMinutes();
        if ($effectiveDuration === null) {
            $effectiveDuration = $service->getDurationMinutes();
        }

        return new ServiceDuration($effectiveDuration, $effectiveDuration + $service->getBufferMinutes());
    }
}
