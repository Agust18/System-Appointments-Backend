<?php
namespace App\Dto\Request\Patient;

final readonly class PatientDataRequest
{
    public function __construct(
        private string $birthDate,
        
    ) {}

    public function getBirthDate(): string
    {
        return $this->birthDate;
    }

    public static function rules(): array
    {
        return [
            'birthDate' => 'required|valid_date[Y-m-d]',
        ];
    }

    public static function messages(): array
    {
        return [
            'birthDate' => [
                'required'   => 'Ingresá la fecha de nacimiento.',
                'valid_date' => 'La fecha debe tener el formato Y-m-d (por ejemplo, 1990-05-20).',
            ],
        ];
    }
}