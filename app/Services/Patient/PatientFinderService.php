<?php
namespace App\Services\Patient;

use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Response\Patient\PatientResponse;
use App\Entity\Patient\Patient;
use App\Exception\Patient\PatientNotFoundException;
use App\Exception\Patient\PatientNotFoundExceptionByUserException;
use App\Models\Patient\PatientModel;

final class PatientFinderService
{
    private PatientModel $patientModel;
    private PatientToPatientResponseConverter $converter;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientToPatientResponseConverter();
    }

    public function find(int $id): Patient
    {
        $patient = $this->patientModel->find($id);
        
        if (!$patient) {
            throw new PatientNotFoundException($id);
        }
       return $patient;
    }

    public function findByUserId(int $userId): PatientResponse
    {
        $patient = $this->patientModel->findByUserId($userId);
        
        if (!$patient) {
             throw new PatientNotFoundExceptionByUserException($userId);
        }
        
        $response = $this->converter->convert($patient);
        return $response;
    }
} 