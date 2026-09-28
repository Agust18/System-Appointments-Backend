<?php
namespace App\Services\Patient;

use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Entity\Patient\Patient;
use App\Models\Patient\PatientModel;
use App\Services\Patient\Validations\PatientCreateValidatorService;


final class PatientCreatorService
{
    private PatientModel $patientModel;
    private PatientToPatientResponseConverter $converter;
    private PatientCreateValidatorService $validator;
    

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientToPatientResponseConverter();
       
        $this->validator = new PatientCreateValidatorService();
    }

    public function create(PatientRequest $request): PatientResponse
    {
        $this->validator->validate($request);

        $patient = Patient::FromRequest($request);

        $Patient = $this->patientModel->insert($patient);
        
        $patientResponse = $this->converter->convert($Patient);

        return $patientResponse;
    }
}