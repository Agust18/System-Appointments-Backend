<?php
namespace App\Services\Appointment\Validations;

use App\Exception\Validation\ValidationException;
use DateTime;

final class AppointmentAvailabilityRangeValidatorService
{
    private const MAX_RANGE_DAYS = 60;

    public function validate(string $from, string $to): void
    {
        $errors = [];

        $fromDt = $this->parse($from);
        if ($fromDt === null) {
            $errors['from'] = 'El formato de from debe ser YYYY-MM-DD';
        }

        $toDt = $this->parse($to);
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

    private function parse(string $date): ?DateTime
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return ($parsed !== false && $parsed->format('Y-m-d') === $date) ? $parsed : null;
    }
}
