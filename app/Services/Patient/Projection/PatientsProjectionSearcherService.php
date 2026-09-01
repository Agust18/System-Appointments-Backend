<?php
namespace App\Services\Patient\Projection;

use App\Converter\Patient\Projection\PatientProjectionToPatientProjectionResponseConverter;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Dto\Response\Patient\Projection\PatientProjectionResponse;
use App\Models\Patient\PatientModel;

final class PatientsProjectionSearcherService
{
    private PatientModel $patientModel;
    private PatientProjectionToPatientProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientProjectionToPatientProjectionResponseConverter();
    }

    /**
     * @return PatientProjectionResponse[]
     */
    public function searchResponses(PatientFilterRequest $request): array
    {
        $entities = $this->patientModel->searchProjection($request);
        $total = $this->patientModel->countProjection($request);

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