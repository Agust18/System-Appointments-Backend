<?php
namespace App\Services\Appointment\Validations;

use App\Exception\Validation\ValidationException;

final class AppointmentAvailabilityQueryValidatorService
{
    public function validate(int $medicalId, int $serviceId): void
    {
        $errors = [];

        if ($medicalId <= 0) {
            $errors['medicalId'] = 'medicalId debe ser un entero positivo';
        }

        if ($serviceId <= 0) {
            $errors['serviceId'] = 'serviceId debe ser un entero positivo';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
