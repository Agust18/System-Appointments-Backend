<?php

namespace App\Dto\Request\Platform;

use App\Support\IdentityNormalizer;

final readonly class TenantSettingsUpdateRequest
{
    private const FIELDS = ['name', 'replyToEmail', 'frontendUrl', 'receptionEmail', 'receptionEmailEnabled',
        'contactPhone', 'contactWhatsapp', 'contactEmail', 'address', 'openingHours', 'landingEnabled',
        'heroTitle', 'heroSubtitle'];
    private const CLEARABLE = ['replyToEmail', 'frontendUrl', 'receptionEmail',
        'contactPhone', 'contactWhatsapp', 'contactEmail', 'address', 'openingHours',
        'heroTitle', 'heroSubtitle'];

    public function __construct(
        private ?string $name,
        private ?string $replyToEmail,
        private ?string $frontendUrl,
        private ?string $receptionEmail,
        private ?bool $receptionEmailEnabled,
        private ?string $contactPhone,
        private ?string $contactWhatsapp,
        private ?string $contactEmail,
        private ?string $address,
        private ?string $openingHours,
        private ?bool $landingEnabled,
        private ?string $heroTitle,
        private ?string $heroSubtitle,
        private array $presentFields = [],
    ) {}

    public static function sanitize(array $parameters): array
    {
        $data = array_intersect_key($parameters, array_flip(self::FIELDS));

        foreach ($data as $field => $value) {
            if (is_string($value)) {
                $value = trim($value);
                if ($field === 'contactWhatsapp') {
                    $value = IdentityNormalizer::whatsapp($value) ?? '';
                }
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
            $data['contactPhone'] ?? null,
            $data['contactWhatsapp'] ?? null,
            $data['contactEmail'] ?? null,
            $data['address'] ?? null,
            $data['openingHours'] ?? null,
            isset($data['landingEnabled']) ? (bool) $data['landingEnabled'] : null,
            $data['heroTitle'] ?? null,
            $data['heroSubtitle'] ?? null,
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

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function getContactWhatsapp(): ?string
    {
        return $this->contactWhatsapp;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function getOpeningHours(): ?string
    {
        return $this->openingHours;
    }

    public function getLandingEnabled(): ?bool
    {
        return $this->landingEnabled;
    }

    public function getHeroTitle(): ?string
    {
        return $this->heroTitle;
    }

    public function getHeroSubtitle(): ?string
    {
        return $this->heroSubtitle;
    }

    public static function rules(): array
    {
        return [
            'name'                  => 'permit_empty|string|min_length[2]|max_length[150]',
            'replyToEmail'          => 'permit_empty|string|valid_email|max_length[255]',
            'frontendUrl'           => 'permit_empty|string|max_length[255]',
            'receptionEmail'        => 'permit_empty|string|valid_email|max_length[255]',
            'receptionEmailEnabled' => 'permit_empty|in_list[0,1]',
            'contactPhone'          => 'permit_empty|string|max_length[50]',
            'contactWhatsapp'       => 'permit_empty|string|max_length[20]',
            'contactEmail'          => 'permit_empty|string|valid_email|max_length[255]',
            'address'               => 'permit_empty|string|max_length[255]',
            'openingHours'          => 'permit_empty|string|max_length[500]',
            'landingEnabled'        => 'permit_empty|in_list[0,1]',
            'heroTitle'             => 'permit_empty|string|max_length[255]',
            'heroSubtitle'          => 'permit_empty|string|max_length[255]',
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
            'contactPhone' => [
                'string'     => 'El teléfono no es válido.',
                'max_length' => 'El teléfono es muy largo.',
            ],
            'contactWhatsapp' => [
                'string'     => 'El WhatsApp no es válido.',
                'max_length' => 'El WhatsApp es muy largo (máx. 20 dígitos).',
            ],
            'contactEmail' => [
                'string'      => 'El email de contacto no es válido.',
                'valid_email' => 'El email de contacto no es válido.',
                'max_length'  => 'El email de contacto es muy largo.',
            ],
            'address' => [
                'string'     => 'La dirección no es válida.',
                'max_length' => 'La dirección es muy larga.',
            ],
            'openingHours' => [
                'string'     => 'Los horarios no son válidos.',
                'max_length' => 'Los horarios son muy largos.',
            ],
            'landingEnabled' => [
                'in_list' => 'landingEnabled debe ser true o false.',
            ],
            'heroTitle' => [
                'string'     => 'El título de portada no es válido.',
                'max_length' => 'El título de portada es muy largo (máx. 255 caracteres).',
            ],
            'heroSubtitle' => [
                'string'     => 'El subtítulo de portada no es válido.',
                'max_length' => 'El subtítulo de portada es muy largo (máx. 255 caracteres).',
            ],
        ];
    }
}
