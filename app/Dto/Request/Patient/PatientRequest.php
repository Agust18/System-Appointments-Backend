<?php
namespace App\Dto\Request\Patient;

use DateTime;

final readonly class PatientRequest
{
    public function __construct(
        public int $userId,
        public string $birthDate,
       
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