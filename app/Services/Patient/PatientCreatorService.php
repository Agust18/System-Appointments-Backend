<?php
namespace App\Services\Patient;

use App\Constants\Role;
use App\Converter\Patient\PatientToPatientResponseConverter;
use App\Dto\Request\Patient\PatientRequest;
use App\Dto\Response\Patient\PatientResponse;
use App\Entity\Patient\Patient;
use App\Exception\User\MissingRoleIdException;
use App\Exception\User\RoleMismatchException;
use App\Models\Patient\PatientModel;
use App\Services\auth\SendVerificationEmailService;
use App\Services\Patient\Validations\PatientCreateValidatorService;
use App\Services\User\UserCreatorService;
use App\Services\User\UserFinderService;
use CodeIgniter\Database\BaseConnection;

final class PatientCreatorService
{
    private PatientModel $patientModel;
    private PatientToPatientResponseConverter $converter;
    private PatientCreateValidatorService $validator;
    private UserCreatorService $userCreatorService;
    private UserFinderService $userFinderService;
    private SendVerificationEmailService $emailVerificationService;
    private BaseConnection $db;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->converter = new PatientToPatientResponseConverter();
        $this->validator = new PatientCreateValidatorService();
        $this->userCreatorService = new UserCreatorService();
        $this->userFinderService = new UserFinderService();
        $this->emailVerificationService = new SendVerificationEmailService();
        $this->db = \Config\Database::connect();
    }

    public function create(PatientRequest $request, bool $isVerified = false): PatientResponse
    {
        $requestedRole = $request->getUserRequest()->getRoleId();

        if ($requestedRole === null) {
            throw new MissingRoleIdException();
        }
        if ($requestedRole !== Role::PATIENT) {
            throw new RoleMismatchException();
        }

        $this->db->transStart();

        try {
            $newUser = $this->userCreatorService->create($request->getUserRequest(), $isVerified);

            $this->validator->validate($newUser->id, $request->getPatientDataRequest());

            $patient = Patient::fromRequest($newUser->id, $request->getPatientDataRequest());

            $newPatient = $this->patientModel->insert($patient);

            $this->db->transComplete();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e;
        }

        $patientResponse = $this->converter->convert($newPatient);

        if (!$isVerified) {
            $this->emailVerificationService->send(
                $this->userFinderService->find($newUser->id)
            );
        }

        return $patientResponse;
    }
}
