<?php
namespace App\Services\Appointment;

use App\exception\Appointment\AppointmentNotFoundException;
use App\Models\Appointment\AppointmentModel;
use DateTime;

final class AppointmentDeleterService
{
    private AppointmentModel $appointmentModel;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
    }

    public function delete(int $id): void
    {
        $appointment = $this->appointmentModel->find($id);

        if (!$appointment) {
            throw new AppointmentNotFoundException($id);
        }
        
        $today = new DateTime();
        $today->setTime(0, 0);
        $appointmentDate = $appointment->getFecha();
        $appointmentDate->setTime(0, 0);

        if ($appointmentDate < $today) {
            throw new \Exception('No se puede eliminar un turno que ya pasó');
        }


        $this->appointmentModel->delete($id);


    }
}