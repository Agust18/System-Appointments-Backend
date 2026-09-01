<?php 
namespace App\Services\Patient;

use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Request\Patient\PatientFilterRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Models\Patient\PatientModel;

final class PatientsSearcherService 
{
    private PatientModel $patientModel;
    private PatientToPatientResponseConverter $converter;

    public function __construct() 
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientToPatientResponseConverter();
    }

    /**
     * @return PatientResponse[]
     */
    public function searchResponses(PatientFilterRequest $request): array
    {
        $entities = $this->patientModel->search($request);

        $responses = [];
        foreach ($entities as $entity) {
            $responses[] = $this->converter->convert($entity);
        }

        return $responses;
    }
}