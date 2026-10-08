<?php

namespace App\Dto\Request\Platform;

/**
 * El slug no se modifica: es el subdominio del Tenant y el valor del header X-Tenant.
 */
final readonly class TenantUpdateRequest
{
    public function __construct(
        private ?string $name,
        private ?string $status,
    ) {}

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public static function rules(): array
    {
        return [
            'name'   => 'permit_empty|min_length[2]|max_length[150]',
            'status' => 'permit_empty|in_list[active,suspended]',
        ];
    }

    public static function messages(): array
    {
        return [
            'name' => [
                'min_length' => 'El nombre es muy corto.',
                'max_length' => 'El nombre es muy largo.',
            ],
            'status' => [
                'in_list' => 'El estado debe ser active o suspended.',
            ],
        ];
    }
}
