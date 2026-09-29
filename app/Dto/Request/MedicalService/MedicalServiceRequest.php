<?php
namespace App\Dto\Request\MedicalService;

final readonly class MedicalServiceRequest
{
    public function __construct(
        private int $serviceId,
        private ?float $customPrice = null,
        private ?int $customDurationMinutes = null,
        private bool $isActive = true
    ) {
    }

    public function getServiceId(): int
    {
        return $this->serviceId;
    }

    public function getCustomPrice(): ?float
    {
        return $this->customPrice;
    }

    public function getCustomDurationMinutes(): ?int
    {
        return $this->customDurationMinutes;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public static function rules(): array
    {
        return [
            'serviceId'             => 'required|is_natural_no_zero',
            'customPrice'           => 'permit_empty|decimal|greater_than_equal_to[0]',
            'customDurationMinutes' => 'permit_empty|is_natural_no_zero',
            'isActive'              => 'permit_empty|in_list[0,1]',
        ];
    }

    public static function messages(): array
    {
        return [
            'serviceId' => [
                'required'           => 'Elegí un servicio.',
                'is_natural_no_zero' => 'Elegí un servicio válido.',
            ],
            'customPrice' => [
                'decimal'               => 'El precio no es válido.',
                'greater_than_equal_to' => 'El precio no puede ser negativo.',
            ],
            'customDurationMinutes' => [
                'is_natural_no_zero' => 'La duración no es válida.',
            ],
            'isActive' => [
                'in_list' => 'El estado no es válido.',
            ],
        ];
    }
}