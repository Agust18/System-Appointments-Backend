<?php
namespace App\Validation;

/**
 * Reglas y mensajes de los datos de identidad (nombre, apellido, email, teléfono,
 * contraseña y DNI), compartidos por todos los requests que los reciben.
 *
 * Los formatos no llevan required/permit_empty: cada request decide si el campo
 * es obligatorio con required() u optional().
 *
 * Los mensajes tienen dos voces: SELF cuando la persona completa sus propios datos
 * (reserva pública, registro) y STAFF cuando los carga el personal (panel).
 * El frontend público depende de los textos de SELF.
 */
final class IdentityRules
{
    public const SELF  = 'self';
    public const STAFF = 'staff';

    public const FIRST_NAME = 'min_length[2]|max_length[100]';
    public const LAST_NAME  = 'min_length[2]|max_length[100]';
    public const EMAIL      = 'valid_email|max_length[150]';
    public const PHONE      = 'is_natural|min_length[10]|max_length[15]';
    public const PASSWORD   = 'min_length[8]';
    public const DNI        = 'is_natural_no_zero|min_length[7]|max_length[8]';

    public static function required(string $format): string
    {
        return 'required|' . $format;
    }

    public static function optional(string $format): string
    {
        return 'permit_empty|' . $format;
    }

    /**
     * Mensajes de todos los campos de identidad. Un request puede usarlos aunque no
     * valide todos los campos: CI4 ignora los mensajes de campos sin regla.
     */
    public static function messages(string $voice = self::STAFF): array
    {
        $self = $voice === self::SELF;

        return [
            'firstName' => [
                'required'   => $self ? 'Ingresá tu nombre.' : 'Ingresá el nombre.',
                'min_length' => 'El nombre es muy corto.',
                'max_length' => 'El nombre es muy largo.',
            ],
            'lastName' => [
                'required'   => $self ? 'Ingresá tu apellido.' : 'Ingresá el apellido.',
                'min_length' => 'El apellido es muy corto.',
                'max_length' => 'El apellido es muy largo.',
            ],
            'email' => [
                'required'    => $self ? 'Ingresá tu email.' : 'Ingresá un email.',
                'valid_email' => 'Ese email no es válido.',
                'max_length'  => 'El email es muy largo.',
            ],
            'phone' => [
                'required'   => $self ? 'Ingresá tu teléfono.' : 'Ingresá el teléfono.',
                'is_natural' => 'El teléfono solo puede tener números.',
                'min_length' => 'El teléfono es muy corto.',
                'max_length' => 'El teléfono es muy largo.',
            ],
            'password' => [
                'required'   => 'Ingresá una contraseña.',
                'min_length' => 'La contraseña necesita al menos 8 caracteres.',
            ],
            'dni' => [
                'required'           => $self ? 'Ingresá tu DNI.' : 'Ingresá el DNI.',
                'is_natural_no_zero' => $self ? 'El DNI debe ser numérico.' : 'El DNI no es válido.',
                'min_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
                'max_length'         => 'El DNI debe tener entre 7 y 8 dígitos.',
            ],
        ];
    }
}
