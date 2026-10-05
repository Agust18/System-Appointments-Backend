<?php
namespace App\Services\Appointment\Validations;

use App\Exception\Validation\ValidationException;

final class AppointmentAvailabilityRangeValidatorService
{
    private const MAX_RANGE_DAYS = 60;

    private AppointmentAvailabilityDateValidatorService $dateValidator;

    public function __construct()
    {
        $this->dateValidator = new AppointmentAvailabilityDateValidatorService();
    }

    public function validate(string $from, string $to): void
    {
        $errors = [];

        $fromDt = $this->dateValidator->parse($from);
        if ($fromDt === null) {
            $errors['from'] = 'El formato de from debe ser YYYY-MM-DD';
        }

        $toDt = $this->dateValidator->parse($to);
        if ($toDt === null) {
            $errors['to'] = 'El formato de to debe ser YYYY-MM-DD';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        if ($fromDt > $toDt) {
            throw new ValidationException(['range' => 'from no puede ser posterior a to']);
        }

        $diffDays = $fromDt->diff($toDt)->days;
        if ($diffDays > self::MAX_RANGE_DAYS) {
            throw new ValidationException(['range' => 'El rango no puede superar ' . self::MAX_RANGE_DAYS . ' días']);
        }
    }
}
