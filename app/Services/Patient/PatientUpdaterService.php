<?php
namespace App\Services\Patient;

use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Models\Patient\PatientModel;
use App\Services\Patient\PatientFinderService;
use DateTime;

final class PatientUpdaterService
{
    private PatientModel $patientModel;
    private PatientToPatientResponseConverter $converter;
    private PatientFinderService $patientFinderService;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientToPatientResponseConverter();
        $this->patientFinderService = new PatientFinderService();
    }

    public function update(PatientUpdateRequest $request, int $id): PatientResponse
    {
        $patient = $this->patientFinderService->find($id);

        if ($request->getBirthDate() && $request->getBirthDate() > date('Y-m-d')) {
            throw new \Exception('La fecha de nacimiento no puede ser futura');
        }

        $patient->modify($request);

        $updatedPatient = $this->patientModel->update($patient);

        $response = $this->converter->convert($updatedPatient);

        return $response;
    }
}