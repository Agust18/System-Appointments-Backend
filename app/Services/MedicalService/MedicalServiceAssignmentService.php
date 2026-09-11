<?php
namespace App\Services\MedicalService;

use App\Converter\MedicalService\MedicalServiceToMedicalServiceResponseConverter;
use App\Dto\Request\MedicalService\MedicalServiceRequest;
use App\Dto\Response\MedicalService\MedicalServiceResponse;
use App\Entity\MedicalService\MedicalService;
use App\Models\MedicalService\MedicalServiceModel;
use App\Services\Medical\MedicalFinderService;
use App\Services\Service\ServiceFinderService;
use Exception;

final class MedicalServiceAssignmentService
{
    private MedicalServiceModel $medicalServiceModel;
    private MedicalFinderService $medicalFinderService;
    private ServiceFinderService $serviceFinderService;
    private MedicalServiceToMedicalServiceResponseConverter $converter;

    public function __construct()
    {
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->medicalFinderService = new MedicalFinderService();
        $this->serviceFinderService = new ServiceFinderService();
        $this->converter = new MedicalServiceToMedicalServiceResponseConverter();
    }

    public function assign(MedicalServiceRequest $request, int $medicalId): MedicalServiceResponse
    {
        $this->medicalFinderService->find($medicalId);
        $service = $this->serviceFinderService->find($request->getServiceId());

        if (!$service->isActive()) {
            throw new Exception('El servicio seleccionado se encuentra inactivo.');
        }

        $medicalService = MedicalService::fromRequest($request, $medicalId);
        $saved = $this->medicalServiceModel->insertOrUpdate($medicalService);

        return $this->converter->convert($saved);
    }
}