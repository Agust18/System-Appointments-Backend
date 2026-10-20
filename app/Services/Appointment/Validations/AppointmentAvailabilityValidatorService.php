<?php
namespace App\Services\Appointment\Validations;

use App\Exception\Validation\ValidationException;
use DateTime;

/**
 * Validaciones de los parámetros de disponibilidad: ids, fecha única y rango.
 */
final class AppointmentAvailabilityValidatorService
{
    private const MAX_RANGE_DAYS = 60;

    public function validateQuery(int $medicalId, int $serviceId): void
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

    /**
     * Turno a excluir de los ocupados (edición): ausente (null) o entero positivo.
     */
    public function validateExcludeAppointmentId(mixed $excludeAppointmentId): void
    {
        if ($excludeAppointmentId === null) {
            return;
        }

        // Un array (?excludeAppointmentId[]=1) o un texto no numérico no son un id válido.
        if (!is_string($excludeAppointmentId) || !ctype_digit($excludeAppointmentId) || (int) $excludeAppointmentId <= 0) {
            throw new ValidationException(['excludeAppointmentId' => 'excludeAppointmentId debe ser un entero positivo']);
        }
    }

    public function validateDate(string $fecha, string $field = 'fecha'): void
    {
        if ($this->parse($fecha) === null) {
            throw new ValidationException([$field => "El formato de {$field} debe ser YYYY-MM-DD"]);
        }
    }

    public function validateRange(string $from, string $to): void
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

    /**
     * Fecha Y-m-d real del calendario (rechaza formatos libres y días inexistentes como 2026-02-30).
     */
    private function parse(string $date): ?DateTime
    {
        $parsed = DateTime::createFromFormat('Y-m-d', $date);

        return ($parsed !== false && $parsed->format('Y-m-d') === $date) ? $parsed : null;
    }
}
