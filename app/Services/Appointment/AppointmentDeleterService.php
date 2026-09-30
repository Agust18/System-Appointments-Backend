<?php
namespace App\Services\Appointment;


use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\Validations\AppointmentDeleteValidatorService;
use App\Services\Appointment\AppointmentFinderService;

final class AppointmentDeleterService
{
    private AppointmentModel $appointmentModel;
    private AppointmentDeleteValidatorService $validator;
    private AppointmentFinderService $finder;
    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->validator       = new AppointmentDeleteValidatorService();
        $this->finder = new AppointmentFinderService();
    }

    public function delete(int $id): void
    {
        $appointment = $this->finder->find($id);

        $this->validator->validate($id,$appointment);
        
        $this->appointmentModel->delete($id);


    }
}