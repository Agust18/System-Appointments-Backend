<?php
namespace App\Services\MedicalService\Projection;

use App\Converter\MedicalService\Projection\MedicalServiceProjectionToPublicMedicalServiceResponseConverter;
use App\Dto\Response\MedicalService\PublicMedicalServiceResponse;
use App\Exception\Appointment\MedicalNotAvailableException;
use App\Models\MedicalService\MedicalServiceModel;
use App\Services\Medical\MedicalFinderService;
use App\Services\User\UserFinderService;

/**
 * Servicios que ofrece un médico, para el wizard de reserva pública. El modelo ya
 * descarta las relaciones y los servicios inactivos; acá se exige además que el
 * médico y su usuario estén activos, igual que al reservar.
 */
final class PublicMedicalServicesFinderService
{
    private MedicalFinderService $medicalFinderService;
    private UserFinderService $userFinderService;
    private MedicalServiceModel $medicalServiceModel;
    private MedicalServiceProjectionToPublicMedicalServiceResponseConverter $converter;

    public function __construct()
    {
        $this->medicalFinderService = new MedicalFinderService();
        $this->userFinderService = new UserFinderService();
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->converter = new MedicalServiceProjectionToPublicMedicalServiceResponseConverter();
    }

    /**
     * @return PublicMedicalServiceResponse[]
     */
    public function findByMedicalResponses(int $medicalId): array
    {
        $medical = $this->medicalFinderService->find($medicalId);
        $doctor = $this->userFinderService->find($medical->getUserId());

        if (!$medical->getIsActive() || !$doctor->getIsActive()) {
            throw new MedicalNotAvailableException();
        }

        $responses = [];
        foreach ($this->medicalServiceModel->findServicesByMedicalIdProjection($medicalId) as $projection) {
            $responses[] = $this->converter->convert($projection);
        }

        return $responses;
    }
}
