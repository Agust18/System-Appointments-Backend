<?php
namespace App\Dto\Request\DayOff;

/**
 * `medicalId` y `reason` admiten NULL como valor ("toda la clínica" / sin motivo), así que
 * "no vino" y "vino null" se distinguen con los flags `hasMedicalId` / `hasReason`.
 */
final readonly class DayOffUpdateRequest
{
    public function __construct(
        private ?int $medicalId = null,
        private ?string $dateFrom = null,
        private ?string $dateTo = null,
        private ?string $reason = null,
        private bool $medicalIdPresent = false,
        private bool $reasonPresent = false
    ) {
    }

    public function getMedicalId(): ?int
    {
        return $this->medicalId;
    }

    public function hasMedicalId(): bool
    {
        return $this->medicalIdPresent;
    }

    public function getDateFrom(): ?string
    {
        return $this->dateFrom;
    }

    public function getDateTo(): ?string
    {
        return $this->dateTo;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function hasReason(): bool
    {
        return $this->reasonPresent;
    }

    public static function rules(): array
    {
        return [
            'medicalId' => 'permit_empty|is_natural_no_zero',
            'dateFrom'  => 'permit_empty|valid_date[Y-m-d]',
            'dateTo'    => 'permit_empty|valid_date[Y-m-d]',
            'reason'    => 'permit_empty|string|max_length[255]',
        ];
    }

    public static function messages(): array
    {
        return DayOffRequest::messages();
    }
}
