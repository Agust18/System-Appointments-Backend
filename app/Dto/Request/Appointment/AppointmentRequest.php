<?php
namespace App\Dto\Request\Appointment;

use DateTime;

final readonly class AppointmentRequest
{
    public function __construct(
        private int $medicalId,
        private int $patientId,
        private int $serviceId,
        private string $fecha,
        private string $hora,
        private ?string $nota,
        private string $status = "pending",
    ) {
    }

    public function getMedicalId(): int
    {
        return $this->medicalId;
    }
    public function getPatientId(): int
    {
        return $this->patientId;
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
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return array<string, string>
     */
    public static function rules(): array
    {
        return [
            'medicalId' => 'required|is_natural_no_zero',
            'patientId' => 'required|is_natural_no_zero',
            'serviceId' => 'required|is_natural_no_zero',
            'fecha' => 'required|valid_date[Y-m-d]',
            'hora' => 'required',
            'nota' => 'permit_empty|string|max_length[500]',
            'status' => 'permit_empty|string|in_list[pending,confirmed,cancelled,completed]',
        ];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function messages(): array
    {
        return [
            'medicalId' => [
                'required' => 'Elegir Profesional para atender el turno.',
                'is_natural_no_zero' => 'El ID del médico debe ser un número entero mayor que cero.',
            ],
            'patientId' => [
                'required' => 'Seleccionar paciente|cliente.',
                'is_natural_no_zero' => 'El ID del paciente debe ser un número entero mayor que cero.',
            ],
            'serviceId' => [
                'required' => 'Falta seleccionar un servicio para el turno.',
                'is_natural_no_zero' => 'El ID del servicio debe ser un número entero mayor que cero.',
            ],
            'fecha' => [
                'required' => 'Seleccionar Fecha para el turno.',
                'valid_date' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 2025-01-31).',
            ],
            'hora' => [
                'required' => 'Elegir Hora del turno.',
            ],
            'nota' => [
                'string' => 'La nota debe ser texto.',
                'max_length' => 'La nota no puede superar los 500 caracteres.',
            ],
            'status' => [
                'in_list' => 'El estado debe ser uno de: pendiente, confirmado, cancelado, completado.',
            ],
        ];
    }
}