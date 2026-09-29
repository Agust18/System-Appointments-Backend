<?php
namespace App\Dto\Request\Role;

final readonly class RoleUpdateRequest
{
    public function __construct(
        private ?string $name = null
    ) {}

    public function getName(): ?string
    {
        return $this->name;
    }

    public static function rules(): array
    {
        return [
            'name' => 'permit_empty|min_length[3]|max_length[20]',
        ];
    }

    public static function messages(): array
    {
        return [
            'name' => [
                'min_length' => 'El nombre del rol debe tener al menos 3 caracteres.',
                'max_length' => 'El nombre del rol no puede superar los 20 caracteres.',
            ],
        ];
    }
}