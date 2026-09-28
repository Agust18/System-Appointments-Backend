<?php
namespace App\Services\Appointment\Validations;

use App\Exception\Appointment\AppointmentPastCannotBeDeletedException;
use DateTime;

final class AppointmentDeleteValidatorService
{
    

    public function __construct()
    {
        
    }

    public function validate(int $id,$appointment): void
    {
      
        $today = new DateTime();
        $today->setTime(0, 0);
        $appointmentDate = $appointment->getFecha();
        $appointmentDate->setTime(0, 0);

        if ($appointmentDate < $today) {
            throw new AppointmentPastCannotBeDeletedException();
        }


        


    }
}