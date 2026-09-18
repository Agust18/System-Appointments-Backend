<?php

namespace App\Services\Patient\Validations;

use App\Constants\Role;
use App\Dto\Request\Patient\PatientUpdateRequest;
use App\Entity\Patient\Patient;
use App\Exception\Patient\InvalidBirthDateException;
use DateTime;

final class PatientUpdateValidatorService
{
    public function validate(PatientUpdateRequest $request, Patient $patient): void
    {
        if ($request->getBirthDate() !== null) {
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
