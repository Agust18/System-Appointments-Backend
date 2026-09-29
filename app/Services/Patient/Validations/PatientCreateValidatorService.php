<?php
namespace App\Services\Patient\Validations;

use App\Constants\Role;
use App\Dto\Request\Patient\PatientDataRequest;
use App\Exception\Patient\InvalidBirthDateException;
use App\Exception\Patient\PatientAlreadyExistsException;
use App\Exception\Patient\UserNotPatientException;
use App\Models\Patient\PatientModel;
use App\Services\User\UserFinderService;
use DateTime;

final class PatientCreateValidatorService
{
    private PatientModel $patientModel;
    private UserFinderService $userFinderService;

    public function __construct()
    {
        $this->patientModel = new PatientModel();

        $this->userFinderService = new UserFinderService();
    }

    public function validate(int $userId, PatientDataRequest $request): void
    {
        $user = $this->userFinderService->find($userId);

        if ($user->getRoleId() !== Role::PATIENT) {
            throw new UserNotPatientException();
        }

        $existingPatient = $this->patientModel->findByUserId($userId);
        if ($existingPatient) {
            throw new PatientAlreadyExistsException($userId);
        }

        if ($request->getBirthDate()) {
            $birthDate = new DateTime($request->getBirthDate());
            $today = new DateTime();
            $today->setTime(0, 0);
            $birthDate->setTime(0, 0);

            if ($birthDate > $today) {
                throw new InvalidBirthDateException();
            }
        }


    }
}