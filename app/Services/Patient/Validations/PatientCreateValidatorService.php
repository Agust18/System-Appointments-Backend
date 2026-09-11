<?php
namespace App\Services\Patient\Validations;

use App\Constants\Role;
use App\Dto\Request\Patient\PatientRequest;
use App\Exception\Patient\InvalidBirthDateException;
use App\Exception\Patient\PatientAlreadyExistsException;
use App\exception\User\UserNotFoundException;
use App\Exception\User\UserNotPatientException;
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

    public function validate(PatientRequest $request): void
    {
        $user = $this->userFinderService->find($request->getUserId());
      

        if ($user->getRoleId() !== Role::PATIENT) {
            throw new UserNotPatientException($request->getUserId());
        }

        $existingPatient = $this->patientModel->findByUserId($request->getUserId());
        if ($existingPatient) {
            throw new PatientAlreadyExistsException($request->getUserId());
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