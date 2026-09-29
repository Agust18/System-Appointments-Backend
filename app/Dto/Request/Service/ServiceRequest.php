<?php
namespace App\Dto\Request\Service;

final readonly class ServiceRequest
{
    public function __construct(
        private string $name,
        private ?string $description,
        private int $durationMinutes,
        private int $bufferMinutes = 0,
        private float $price = 0.0,
        private int $slotStepMinutes = 15,
        private bool $isActive = true
    ) {
    }


    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getDurationMinutes(): int
    {
        return $this->durationMinutes;
    }

    public function getBufferMinutes(): int
    {
        return $this->bufferMinutes;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getSlotStepMinutes(): int
    {
        return $this->slotStepMinutes;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public static function rules(): array
    {
        return [
            'name'            => 'required|min_length[3]|max_length[100]',
            'description'     => 'required|max_length[500]',
            'durationMinutes' => 'required|is_natural_no_zero|less_than_equal_to[480]',
            'bufferMinutes'   => 'permit_empty|is_natural|less_than_equal_to[120]',
            'price'           => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[999999.99]',
            'slotStepMinutes' => 'permit_empty|is_natural_no_zero|less_than_equal_to[60]',
        ];
    }

    public static function messages(): array
    {
        return [
            'name' => [
                'required'   => 'Ingresá el nombre del servicio.',
                'min_length' => 'El nombre debe tener al menos 3 caracteres.',
                'max_length' => 'El nombre no puede superar los 100 caracteres.',
            ],
            'description' => [
                'required'   => 'Ingresá una descripción.',
                'max_length' => 'La descripción no puede superar los 500 caracteres.',
            ],
            'durationMinutes' => [
                'required'            => 'Ingresá la duración del servicio.',
                'is_natural_no_zero'  => 'La duración debe ser mayor a 0 minutos.',
                'less_than_equal_to'  => 'La duración no puede superar los 480 minutos (8 horas).',
            ],
            'bufferMinutes' => [
                'is_natural'         => 'El buffer no puede ser negativo.',
                'less_than_equal_to' => 'El buffer no puede superar los 120 minutos.',
            ],
            'price' => [
                'decimal'               => 'El precio no es válido.',
                'greater_than_equal_to' => 'El precio no puede ser negativo.',
                'less_than_equal_to'    => 'El precio no puede superar 999.999,99.',
            ],
            'slotStepMinutes' => [
                'is_natural_no_zero' => 'El intervalo debe ser mayor a 0 minutos.',
                'less_than_equal_to' => 'El intervalo no puede superar los 60 minutos.',
            ],
        ];
    }
}
