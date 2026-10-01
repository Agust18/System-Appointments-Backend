<?php
namespace App\Dto\Request\Medical;

final readonly class MedicalDataRequest
{
    public function __construct(
       private string $tuition,
       private int $specialtyId,
       private bool $isActive = true
    ) {
    }

    public function getTuition(): string
    {
        return $this->tuition;
    }
    public function getSpecialtyId(): int
    {
        return $this->specialtyId;
    }
    
    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public static function rules(): array
    {
        return [
            'tuition'     => 'required|max_length[50]',
            'specialtyId' => 'required|is_natural_no_zero',
           
        ];
    }

    public static function messages(): array
    {
        return [
            'tuition' => [
                'required'   => 'Ingresá la matrícula.',
                'max_length' => 'La matrícula es muy larga.',
            ],
            'specialtyId' => [
                'required'           => 'Elegí una especialidad.',
                'is_natural_no_zero' => 'Elegí una especialidad válida.',
            ],
            
        ];
    }
}