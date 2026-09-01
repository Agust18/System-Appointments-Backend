<?php
namespace App\Services\MedicalService\Projection;

use App\Converter\MedicalService\Projection\MedicalServiceProjectionToMedicalServiceProjectionResponseConverter;
use App\Dto\Response\MedicalService\Projection\MedicalServiceProjectionResponse;
use App\Models\MedicalService\MedicalServiceModel;

final class MedicalServicesProjectionFinderService
{
    private MedicalServiceModel $medicalServiceModel;
    private MedicalServiceProjectionToMedicalServiceProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->medicalServiceModel = new MedicalServiceModel();
        $this->converter = new MedicalServiceProjectionToMedicalServiceProjectionResponseConverter();
    }

    /**
     * @return MedicalServiceProjectionResponse[]
     */
    public function findByMedicalResponses(int $medicalId): array
    {
        $projections = $this->medicalServiceModel->findServicesByMedicalIdProjection($medicalId);

        $responses = [];
        foreach ($projections as $projection) {
            $responses[] = $this->converter->convert($projection);
        }

        return $responses;
    }
}
