<?php
namespace App\Services\Patient\Projection;

use App\Converter\Patient\Projection\PatientProjectionToPatientProjectionResponseConverter;
use App\Dto\Response\Patient\Projection\PatientProjectionResponse;
use App\Entity\Patient\Projection\PatientProjection;
use App\Exception\Patient\PatientNotFoundException;
use App\Exception\Patient\PatientNotFoundExceptionByUserException;
use App\Models\Patient\PatientModel;

final class PatientProjectionFinderService
{
    private PatientModel $patientModel;
    private PatientProjectionToPatientProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientProjectionToPatientProjectionResponseConverter();
    }

 
    public function find(int $id): PatientProjection
    {
        $patient = $this->patientModel->findProjection($id);
        
        if (!$patient) {
            throw new PatientNotFoundException($id);
        }
        
        return $patient;
    }

   
    public function findResponse(int $id): PatientProjectionResponse
    {
        $patient = $this->find($id);
        return $this->converter->convert($patient);
    }

   
    public function findByUserIdResponse(int $userId): PatientProjectionResponse
    {
        $patient = $this->patientModel->findByUserIdProjection($userId);
        
        if (!$patient) {
            throw new PatientNotFoundExceptionByUserException($userId);
        }
        
        return $this->converter->convert($patient);
    }
}