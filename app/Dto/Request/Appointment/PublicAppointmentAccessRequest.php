<?php
namespace App\Dto\Request\Appointment;

use App\Validation\IdentityRules;

/**
 * Credenciales de `/consulta`: DNI + código, o el token del link del email.
 */
final readonly class PublicAppointmentAccessRequest
{
    public function __construct(
        private ?int $dni,
        private ?string $code,
        private ?string $token
    ) {}

    public function getDni(): ?int
    {
        return $this->dni;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public static function rules(): array
    {
        return [
            'token' => 'permit_empty|string|max_length[128]',
            'dni'   => 'required_without[token]|permit_empty|' . IdentityRules::DNI,
            'code'  => 'required_without[token]|permit_empty|string|max_length[20]',
        ];
    }

    public static function messages(): array
    {
        return [
            'dni' => [
                'required_without'   => 'Ingresá tu DNI.',
                'is_natural_no_zero' => 'El DNI debe ser numérico.',
                'min_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
                'max_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
            ],
            'code' => [
                'required_without' => 'Ingresá el código de tu turno.',
                'max_length'       => 'El código no es válido.',
            ],
            'token' => [
                'max_length' => 'El link no es válido.',
            ],
        ];
    }
}
