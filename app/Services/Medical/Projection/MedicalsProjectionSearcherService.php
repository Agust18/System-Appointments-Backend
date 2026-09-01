<?php
namespace App\Services\Medical\Projection;

use App\Converter\Medical\Projection\MedicalProjectionToMedicalProjectionResponseConverter;
use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Dto\Response\Medical\Projection\MedicalProjectionResponse;
use App\Models\Medical\MedicalModel;

final class MedicalsProjectionSearcherService
{
    private MedicalModel $medicalModel;
    private MedicalProjectionToMedicalProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->converter = new MedicalProjectionToMedicalProjectionResponseConverter();
    }

    /**
     * @return MedicalProjectionResponse[]
     */
    public function searchResponses(MedicalFilterRequest $request): array
    {
        $entities = $this->medicalModel->searchProjection($request);
        $total = $this->medicalModel->countProjection($request);
        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }
        return [
            'data' => $responses,
            'total' => $total,
        ];
    }

   
}