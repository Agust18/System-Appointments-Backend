<?php
namespace App\Dto\Request\DayOff;

final readonly class DayOffRequest
{
    public function __construct(
        private ?int $medicalId,
        private string $dateFrom,
        private string $dateTo,
        private ?string $reason = null
    ) {
    }

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }

    public function getDateFrom(): string
    {
        return $this->dateFrom;
    }

    public function getDateTo(): string
    {
        return $this->dateTo;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public static function rules(): array
    {
        return [
            'medicalId' => 'permit_empty|is_natural_no_zero',
            'dateFrom'  => 'required|valid_date[Y-m-d]',
            'dateTo'    => 'required|valid_date[Y-m-d]',
            'reason'    => 'permit_empty|string|max_length[255]',
        ];
    }

    public static function messages(): array
    {
        return [
            'medicalId' => [
                'is_natural_no_zero' => 'Elegí un médico válido.',
            ],
            'dateFrom' => [
                'required'   => 'Ingresá la fecha de inicio.',
                'valid_date' => 'La fecha de inicio no es válida.',
            ],
            'dateTo' => [
                'required'   => 'Ingresá la fecha de fin.',
                'valid_date' => 'La fecha de fin no es válida.',
            ],
            'reason' => [
                'max_length' => 'El motivo no puede superar los 255 caracteres.',
            ],
        ];
    }
}
