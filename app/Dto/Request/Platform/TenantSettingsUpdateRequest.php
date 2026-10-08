<?php

namespace App\Dto\Request\Platform;

/**
 * Edición de la configuración de un Tenant desde el panel. Todos los campos son opcionales:
 * uno ausente no se modifica; `null` (o "") limpia `replyToEmail`, `frontendUrl` y `receptionEmail`.
 * Por eso el DTO distingue "ausente" de "null" con has(). El Tenant sale de la URL, nunca del body.
 */
final readonly class TenantSettingsUpdateRequest
{
    private const FIELDS = ['name', 'replyToEmail', 'frontendUrl', 'receptionEmail', 'receptionEmailEnabled'];
    private const CLEARABLE = ['replyToEmail', 'frontendUrl', 'receptionEmail'];

    /** @param array<string, mixed> $data solo los campos presentes en el body */
    private function __construct(private array $data) {}

    /**
     * Se queda solo con los campos conocidos (un `tenantId` del body se ignora). Los strings se
     * recortan y, en los que se pueden limpiar, "" pasa a null.
     *
     * @param array<string, mixed> $parameters
     * @return array<string, mixed>
     */
    public static function sanitize(array $parameters): array
    {
        $data = array_intersect_key($parameters, array_flip(self::FIELDS));

        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $data[$field] = ($value === '' && in_array($field, self::CLEARABLE, true)) ? null : $value;
            }
        }

        return $data;
    }

    /** @param array<string, mixed> $parameters ya validado con rules() */
    public static function fromArray(array $parameters): self
    {
        $data = self::sanitize($parameters);

        if (isset($data['receptionEmailEnabled'])) {
            $data['receptionEmailEnabled'] = (bool) $data['receptionEmailEnabled'];
        }

        return new self($data);
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->data);
    }

    public function isEmpty(): bool
    {
        return $this->data === [];
    }

    public function getName(): ?string
    {
        return $this->data['name'] ?? null;
    }

    public function getReplyToEmail(): ?string
    {
        return $this->data['replyToEmail'] ?? null;
    }

    public function getFrontendUrl(): ?string
    {
        return $this->data['frontendUrl'] ?? null;
    }

    public function getReceptionEmail(): ?string
    {
        return $this->data['receptionEmail'] ?? null;
    }

    public function getReceptionEmailEnabled(): ?bool
    {
        return $this->data['receptionEmailEnabled'] ?? null;
    }

    public static function rules(): array
    {
        return [
            'name'                  => 'permit_empty|string|min_length[2]|max_length[150]',
            'replyToEmail'          => 'permit_empty|string|valid_email|max_length[255]',
            'frontendUrl'           => 'permit_empty|string|max_length[255]',
            'receptionEmail'        => 'permit_empty|string|valid_email|max_length[255]',
            'receptionEmailEnabled' => 'permit_empty|in_list[0,1]',
        ];
    }

    public static function messages(): array
    {
        return [
            'name' => [
                'string'     => 'El nombre no es válido.',
                'min_length' => 'El nombre es muy corto.',
                'max_length' => 'El nombre es muy largo.',
            ],
            'replyToEmail' => [
                'string'      => 'El email de respuesta no es válido.',
                'valid_email' => 'El email de respuesta no es válido.',
                'max_length'  => 'El email de respuesta es muy largo.',
            ],
            'frontendUrl' => [
                'string'     => 'La URL del frontend no es válida.',
                'max_length' => 'La URL del frontend es muy larga.',
            ],
            'receptionEmail' => [
                'string'      => 'El email de recepción no es válido.',
                'valid_email' => 'El email de recepción no es válido.',
                'max_length'  => 'El email de recepción es muy largo.',
            ],
            'receptionEmailEnabled' => [
                'in_list' => 'receptionEmailEnabled debe ser true o false.',
            ],
        ];
    }
}
