<?php
namespace App\Dto\Request\Medical;

final readonly class MedicalUpdateRequest
{
    public function __construct(
        private ?string $tuition = null,
        private ?int $specialtyId = null
    ) {}

    public function getTuition(): ?string
    {
        return $this->tuition;
    }

    public function getSpecialtyId(): ?int
    {
        return $this->specialtyId;
    }

    public static function rules(): array
    {
        return [
            'tuition'     => 'permit_empty|max_length[50]',
            'specialtyId' => 'permit_empty|is_natural_no_zero',
        ];
    }

    public static function messages(): array
    {
        return [
            'tuition' => [
                'max_length' => 'La matrícula es muy larga.',
            ],
            'specialtyId' => [
                'is_natural_no_zero' => 'Elegí una especialidad válida.',
            ],
        ];
    }
}