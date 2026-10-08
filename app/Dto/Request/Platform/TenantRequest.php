<?php

namespace App\Dto\Request\Platform;

use App\Constants\Role;
use App\Dto\Request\User\UserRequest;

/**
 * Alta de un Tenant junto con su primer ADMIN. La contraseña del ADMIN es temporal: la fija quien
 * da de alta el Tenant y se la comunica por fuera (el email llega con el Paso 4).
 */
final readonly class TenantRequest
{
    public function __construct(
        private string $slug,
        private string $name,
        private string $adminFirstName,
        private string $adminLastName,
        private string $adminEmail,
        private int $adminDni,
        private string $adminPassword,
    ) {}

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAdminFirstName(): string
    {
        return $this->adminFirstName;
    }

    public function getAdminLastName(): string
    {
        return $this->adminLastName;
    }

    public function getAdminEmail(): string
    {
        return $this->adminEmail;
    }

    public function getAdminDni(): int
    {
        return $this->adminDni;
    }

    public function getAdminPassword(): string
    {
        return $this->adminPassword;
    }

    /**
     * El primer ADMIN del Tenant, listo para `UserCreatorService`.
     */
    public function toAdminUserRequest(): UserRequest
    {
        return new UserRequest(
            $this->adminFirstName,
            $this->adminLastName,
            $this->adminEmail,
            null,
            $this->adminPassword,
            $this->adminDni,
            true,
            Role::ADMIN
        );
    }

    public static function rules(): array
    {
        return [
            'slug'           => 'required|max_length[63]|regex_match[/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/]',
            'name'           => 'required|min_length[2]|max_length[150]',
            'adminFirstName' => 'required|min_length[2]|max_length[100]',
            'adminLastName'  => 'required|min_length[2]|max_length[100]',
            'adminEmail'     => 'required|valid_email|max_length[255]',
            'adminDni'       => 'required|is_natural_no_zero|min_length[7]|max_length[8]',
            'adminPassword'  => 'required|min_length[8]',
        ];
    }

    public static function messages(): array
    {
        return [
            'slug' => [
                'required'    => 'Ingresá el identificador (slug) del cliente.',
                'max_length'  => 'El identificador es muy largo.',
                'regex_match' => 'El identificador solo admite minúsculas, números y guiones, sin empezar ni terminar con guion.',
            ],
            'name' => [
                'required'   => 'Ingresá el nombre del cliente.',
                'min_length' => 'El nombre es muy corto.',
                'max_length' => 'El nombre es muy largo.',
            ],
            'adminFirstName' => [
                'required'   => 'Ingresá el nombre del administrador.',
                'min_length' => 'El nombre del administrador es muy corto.',
                'max_length' => 'El nombre del administrador es muy largo.',
            ],
            'adminLastName' => [
                'required'   => 'Ingresá el apellido del administrador.',
                'min_length' => 'El apellido del administrador es muy corto.',
                'max_length' => 'El apellido del administrador es muy largo.',
            ],
            'adminEmail' => [
                'required'    => 'Ingresá el email del administrador.',
                'valid_email' => 'El email del administrador no es válido.',
                'max_length'  => 'El email del administrador es muy largo.',
            ],
            'adminDni' => [
                'required'           => 'Ingresá el DNI del administrador.',
                'is_natural_no_zero' => 'El DNI del administrador no es válido.',
                'min_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
                'max_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
            ],
            'adminPassword' => [
                'required'   => 'Ingresá una contraseña temporal para el administrador.',
                'min_length' => 'La contraseña necesita al menos 8 caracteres.',
            ],
        ];
    }
}
