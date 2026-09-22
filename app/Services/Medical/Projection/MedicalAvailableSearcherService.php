<?php

namespace App\Services\Medical\Projection;

use App\Converter\Medical\Projection\MedicalProjectionToMedicalAvailableResponseConverter;
use App\Dto\Request\Medical\MedicalFilterRequest;
use App\Dto\Response\Medical\MedicalAvailableResponse;
use App\Models\Medical\MedicalModel;

final class MedicalAvailableSearcherService
{
    private MedicalModel $medicalModel;
    private MedicalProjectionToMedicalAvailableResponseConverter $converter;

    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->converter = new MedicalProjectionToMedicalAvailableResponseConverter();
    }

    /**
     * @return array{data: array<int, MedicalAvailableResponse>, total: int}
     */
    public function searchResponses(MedicalFilterRequest $request): array
    {
        $entities = $this->medicalModel->searchAvailableProjection($request);
        $responses = [];

        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }

        return [
            'data' => $responses,
            'total' => $this->medicalModel->countAvailableProjection($request),
        ];
    }
}
