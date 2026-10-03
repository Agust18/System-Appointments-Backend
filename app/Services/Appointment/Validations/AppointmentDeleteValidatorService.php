<?php
namespace App\Services\Appointment\Validations;

use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentPastCannotBeDeletedException;
use DateTime;

final class AppointmentDeleteValidatorService
{


    public function __construct()
    {
    }

    public function validate(Appointment $appointment): void
    {
        $today = (new DateTime())->format('Y-m-d');
        $appointmentDate = $appointment->getFecha()->format('Y-m-d');

        if ($appointmentDate < $today) {
            throw new AppointmentPastCannotBeDeletedException();
        }
    }
}