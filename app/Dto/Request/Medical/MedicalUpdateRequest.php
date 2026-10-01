<?php
namespace App\Dto\Request\Medical;

final readonly class MedicalUpdateRequest
{
    public function __construct(
        private ?string $tuition = null,
        private ?int $specialtyId = null,
        private ?bool $isActive = null
    ) {}

    public function getTuition(): ?string
    {
        return $this->tuition;
    }

    public function getSpecialtyId(): ?int
    {
        return $this->specialtyId;
    }


    public function getIsActive(): ?bool
    {
        return $this->isActive;
    }

    public static function rules(): array
    {
        return [
            'tuition'     => 'permit_empty|max_length[50]',
            'specialtyId' => 'permit_empty|is_natural_no_zero',
            'isActive'    => 'permit_empty|in_list[0,1]',
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
            'isActive' => [
                'in_list' => 'El estado no es válido.',
            ],
        ];
    }
}