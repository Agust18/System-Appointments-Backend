<?php
namespace App\Services\Patient\Validations;

use App\Dto\Request\Patient\PatientRequest;
use App\Exception\Patient\InvalidBirthDateException;
use App\Exception\Patient\PatientAlreadyExistsException;
use App\Models\Patient\PatientModel;
use DateTime;

final class PatientCreateValidatorService
{
    private PatientModel $patientModel;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
    }

    public function validate(PatientRequest $request): void
    {
        if ($this->patientModel->findByDni($request->getDni()) !== null) {
            throw new PatientAlreadyExistsException($request->getDni());
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
