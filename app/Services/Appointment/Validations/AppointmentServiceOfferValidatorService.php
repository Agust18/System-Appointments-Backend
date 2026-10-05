<?php
namespace App\Services\Appointment\Validations;

use App\Entity\MedicalService\MedicalService;
use App\Entity\Service\Service;
use App\Exception\Appointment\MedicalServiceNotOfferedException;
use App\Exception\Appointment\ServiceNotAvailableException;

/**
 * Regla de negocio compartida: el servicio existe y está activo, y el médico lo
 * ofrece de forma activa. Recibe las entidades ya cargadas por el caller (que las
 * necesita igual para calcular la duración), así que no hace consultas propias.
 *
 * La usan la disponibilidad (simple y por rango), el validator del horario y el
 * booking atómico, que la repite dentro del lock por TOCTOU.
 */
final class AppointmentServiceOfferValidatorService
{
    public function validate(?Service $service, ?MedicalService $medicalService): void
    {
        if (!$service || !$service->isActive()) {
            throw new ServiceNotAvailableException('El servicio no está disponible.');
        }

        if (!$medicalService) {
            throw new MedicalServiceNotOfferedException('El profesional no ofrece este servicio.');
        }
    }
}
