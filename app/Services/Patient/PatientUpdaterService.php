<?php

namespace App\Services\Patient;

use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Models\Patient\PatientModel;
use App\Services\Patient\Validations\PatientUpdateValidatorService;

final class PatientUpdaterService
{
    private PatientModel $patientModel;
    private PatientToPatientResponseConverter $converter;
    private PatientFinderService $patientFinderService;
    private PatientUpdateValidatorService $validator;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientToPatientResponseConverter();
        $this->patientFinderService = new PatientFinderService();
        $this->validator = new PatientUpdateValidatorService();
    }

    public function update(PatientUpdateRequest $request, int $id): PatientResponse
    {
        $patient = $this->patientFinderService->find($id);

        $this->validator->validate($request, $patient);

        $patient->modify($request);

        return $this->converter->convert($this->patientModel->update($patient));
    }
}
