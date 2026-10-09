<?php

namespace App\Services\Patient;

use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Models\Patient\PatientModel;
use App\Services\Patient\PatientFinderService;
use App\Services\Patient\Validations\PatientUpdateValidatorService;
use App\Services\User\UserUpdaterService;
use CodeIgniter\Database\BaseConnection;

final class PatientUpdaterService
{
    private PatientModel $patientModel;
    private PatientToPatientResponseConverter $converter;
    private PatientFinderService $patientFinderService;
    private PatientUpdateValidatorService $validator;
    private UserUpdaterService $userUpdaterService;
    private BaseConnection $db;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientToPatientResponseConverter();
        $this->patientFinderService = new PatientFinderService();
        $this->validator = new PatientUpdateValidatorService();
        $this->userUpdaterService = new UserUpdaterService();
        $this->db = \Config\Database::connect();
    }

    public function update(PatientUpdateRequest $request, int $id): PatientResponse
    {
        $patient = $this->patientFinderService->find($id);

        $this->validator->validate($request,$patient);

        $this->db->transBegin();

        try {
            $userRequest = $request->toUserUpdaterRequest();

            if ($userRequest !== null) {
                $this->userUpdaterService->update($userRequest, $patient->getUserId());
            }

            $patient->modify($request);

            $updatedPatient = $this->patientModel->update($patient);

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        $response = $this->converter->convert($updatedPatient);

        return $response;
    }
}
