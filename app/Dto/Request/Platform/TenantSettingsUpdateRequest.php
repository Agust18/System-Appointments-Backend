<?php

namespace App\Dto\Request\Platform;

final readonly class TenantSettingsUpdateRequest
{
    private const FIELDS = ['name', 'replyToEmail', 'frontendUrl', 'receptionEmail', 'receptionEmailEnabled'];
    private const CLEARABLE = ['replyToEmail', 'frontendUrl', 'receptionEmail'];

   
    public function __construct(
        private ?string $name,
        private ?string $replyToEmail,
        private ?string $frontendUrl,
        private ?string $receptionEmail,
        private ?bool $receptionEmailEnabled,
        private array $presentFields = [],
    ) {}

  
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

        return new self(
            $data['name'] ?? null,
            $data['replyToEmail'] ?? null,
            $data['frontendUrl'] ?? null,
            $data['receptionEmail'] ?? null,
            isset($data['receptionEmailEnabled']) ? (bool) $data['receptionEmailEnabled'] : null,
            array_keys($data),
        );
    }

    public function has(string $field): bool
    {
        return in_array($field, $this->presentFields, true);
    }

    public function isEmpty(): bool
    {
        return $this->presentFields === [];
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getReplyToEmail(): ?string
    {
        return $this->replyToEmail;
    }

    public function getFrontendUrl(): ?string
    {
        return $this->frontendUrl;
    }

    public function getReceptionEmail(): ?string
    {
        return $this->receptionEmail;
    }

    public function getReceptionEmailEnabled(): ?bool
    {
        return $this->receptionEmailEnabled;
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
