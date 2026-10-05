<?php
namespace App\Services\Appointment\Validations;

use App\Exception\Validation\ValidationException;
use DateTime;

final class AppointmentAvailabilityDateValidatorService
{
    public function validate(string $fecha, string $field = 'fecha'): void
    {
        if ($this->parse($fecha) === null) {
            throw new ValidationException([$field => "El formato de {$field} debe ser YYYY-MM-DD"]);
        }
    }

    /**
     * Fecha Y-m-d real del calendario (rechaza formatos libres y días inexistentes como 2026-02-30).
     */
    public function parse(string $date): ?DateTime
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return ($parsed !== false && $parsed->format('Y-m-d') === $date) ? $parsed : null;
    }
}
