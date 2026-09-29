<?php
namespace App\Dto\Request\Patient;

final readonly class PatientUpdateRequest
{
    public function __construct(
        private ?string $birthDate,
        
        
    ) {}

    public function getBirthDate(): ?string
    {
        return $this->birthDate;
    }

    public static function rules(): array
    {
        return [
            'birthDate' => 'permit_empty|valid_date[Y-m-d]',
        ];
    }

    public static function messages(): array
    {
        return [
            'birthDate' => [
                'valid_date' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 1990-05-20).',
            ],
        ];
    }
}