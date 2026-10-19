<?php
namespace App\Dto\Request\Appointment;

use App\Dto\Request\Patient\GuestPatientRequest;
final readonly class PublicAppointmentRequest
{
    public function __construct(
        private GuestPatientRequest $patient,
        private int $medicalId,
        private int $serviceId,
        private string $fecha,
        private string $hora,
        private ?string $nota = null,
    ) {}

    public function getPatient(): GuestPatientRequest
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
        return array_merge(GuestPatientRequest::rules(), [
            'medicalId' => 'required|is_natural_no_zero',
            'serviceId' => 'required|is_natural_no_zero',
            'fecha'     => 'required|exact_length[10]|valid_date[Y-m-d]',
            'hora'      => 'required|exact_length[5]|valid_date[H:i]',
            'nota'      => 'permit_empty|string|max_length[500]',
        ]);
    }

    public static function messages(): array
    {
        return array_merge(GuestPatientRequest::messages(), [
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
