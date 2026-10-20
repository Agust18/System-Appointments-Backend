<?php
namespace App\Dto\Response\Appointment;

final readonly class AppointmentAccessCodeResponse
{
    public function __construct(
        public string $accessCode
    ) {}
}
