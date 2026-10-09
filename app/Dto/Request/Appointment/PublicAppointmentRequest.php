<?php
namespace App\Dto\Request\Appointment;

use App\Dto\Request\Patient\PublicPatientRequest;

/**
 * Reserva de turno sin cuenta. No tiene `status` ni `patientId`: el estado lo fija
 * el servicio y el paciente se resuelve a partir de la identidad declarada.
 * `hora` se espera como HH:MM (el formato de los slots de disponibilidad).
 */
final readonly class PublicAppointmentRequest
{
    public function __construct(
        private PublicPatientRequest $patient,
        private int $medicalId,
        private int $serviceId,
        private string $fecha,
        private string $hora,
        private ?string $nota = null,
    ) {}

    public function getPatient(): PublicPatientRequest
    {
        return $this->patient;
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getFecha(): string
    {
        return $this->fecha;
    }

    public function getHora(): string
    {
        return $this->hora;
    }

    public function getNota(): ?string
    {
        return $this->nota;
    }

    public static function rules(): array
    {
        return array_merge(PublicPatientRequest::rules(), [
            'medicalId' => 'required|is_natural_no_zero',
            'serviceId' => 'required|is_natural_no_zero',
            // Largo exacto: sin él "2026-10-5" o "9:30" pasarían valid_date, y el nombre del lock
            // del booking (appt_lock_{id}_{fecha}) y la comparación de horas por string dependen
            // del formato canónico con ceros.
            'fecha'     => 'required|exact_length[10]|valid_date[Y-m-d]',
            'hora'      => 'required|exact_length[5]|valid_date[H:i]',
            'nota'      => 'permit_empty|string|max_length[500]',
        ]);
    }

    public static function messages(): array
    {
        return array_merge(PublicPatientRequest::messages(), [
            'medicalId' => [
                'required'           => 'Elegí el profesional que te va a atender.',
                'is_natural_no_zero' => 'El profesional no es válido.',
            ],
            'serviceId' => [
                'required'           => 'Elegí el servicio.',
                'is_natural_no_zero' => 'El servicio no es válido.',
            ],
            'fecha' => [
                'required'     => 'Elegí la fecha del turno.',
                'exact_length' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 2026-10-05).',
                'valid_date'   => 'La fecha debe tener el formato Y-m-d (por ejemplo, 2026-10-05).',
            ],
            'hora' => [
                'required'     => 'Elegí el horario del turno.',
                'exact_length' => 'El horario debe tener el formato HH:MM (por ejemplo, 09:30).',
                'valid_date'   => 'El horario debe tener el formato HH:MM (por ejemplo, 09:30).',
            ],
            'nota' => [
                'max_length' => 'La nota no puede superar los 500 caracteres.',
            ],
        ]);
    }
}
