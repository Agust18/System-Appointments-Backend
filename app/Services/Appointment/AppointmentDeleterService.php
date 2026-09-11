<?php
namespace App\Services\Appointment;

use App\exception\Appointment\AppointmentNotFoundException;
use App\Models\Appointment\AppointmentModel;
use DateTime;
use App\Services\Appointment\Validations\AppointmentDeleteValidatorService;

final class AppointmentDeleterService
{
    private AppointmentModel $appointmentModel;
    private AppointmentDeleteValidatorService $validator;
    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->validator        = new AppointmentDeleteValidatorService();
    }

    public function delete(int $id): void
    {
        $appointment = $this->appointmentModel->find($id);

        if (!$appointment) {
            throw new AppointmentNotFoundException($id);
        }

        $this->validator->validate($id,$appointment);
        
        $this->appointmentModel->delete($id);


    }
}