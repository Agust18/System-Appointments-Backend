<?php
namespace App\Dto\Request\Specialty;

final readonly class SpecialtyRequest
{
    public function __construct(
        private string $name,
        private ?string $description = null
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public static function rules(): array
    {
        return [
            'name'        => 'required|min_length[3]|max_length[50]',
            'description' => 'permit_empty|max_length[500]',
        ];
    }

    public static function messages(): array
    {
        return [
            'name' => [
                'required'   => 'Ingresá el nombre de la especialidad.',
                'min_length' => 'El nombre debe tener al menos 3 caracteres.',
                'max_length' => 'El nombre no puede superar los 50 caracteres.',
            ],
            'description' => [
                'max_length' => 'La descripción no puede superar los 500 caracteres.',
            ],
        ];
    }
}