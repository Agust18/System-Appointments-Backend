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

   

   
}