<?php
namespace App\Dto\Request\Patient;

use DateTime;

final readonly class PatientRequest
{
    public function __construct(
        private int $userId,
        private string $birthDate,
       
    ) {}

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getBirthDate(): string
    {
        return $this->birthDate;
    }
    

   

      
    
}