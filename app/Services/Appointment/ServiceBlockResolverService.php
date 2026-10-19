<?php
namespace App\Services\Appointment;

use App\Models\MedicalService\MedicalServiceModel;
use App\Models\Service\ServiceModel;
use App\Services\Appointment\Validations\AppointmentServiceOfferValidatorService;

/**
 * Resuelve la duración efectiva y el bloque total de un servicio para un médico:
 * carga el servicio y su asignación, valida que esté activo y ofrecido, y calcula.
 * Lo usan la validación del horario, el booking atómico y la disponibilidad.
 */
final class ServiceBlockResolverService
{
    private ServiceModel $serviceModel;
    private MedicalServiceModel $medicalServiceModel;
    private AppointmentServiceOfferValidatorService $serviceOfferValidator;
    private ServiceDurationCalculatorService $durationCalculator;

    public function __construct()
    {
        $this->serviceModel = new ServiceModel();
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->serviceOfferValidator = new AppointmentServiceOfferValidatorService();
        $this->durationCalculator = new ServiceDurationCalculatorService();
    }

    public function resolve(int $medicalId, ?int $serviceId): ServiceDuration
    {
        $service = null;
        $medicalService = null;

        if ($serviceId !== null) {
            $service = $this->serviceModel->find($serviceId);
            $medicalService = $this->medicalServiceModel->findActiveByMedicalAndService($medicalId, $serviceId);
        }

        $this->serviceOfferValidator->validate($service, $medicalService);

        return $this->durationCalculator->calculate($service, $medicalService);
    }
}
