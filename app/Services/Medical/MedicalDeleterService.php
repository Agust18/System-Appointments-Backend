<?php
namespace App\Services\Medical;

use App\Exception\Medical\MedicalHasAppointmentsException;
use App\Services\Medical\MedicalFinderService;
use App\Models\Appointment\AppointmentModel;
use App\Models\Medical\MedicalModel;

final class MedicalDeleterService
{
    private MedicalModel $medicalModel;
    private MedicalFinderService $medicalFinderService;
    private AppointmentModel $appointmentModel;
    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->appointmentModel = new AppointmentModel();
        $this->medicalFinderService = new MedicalFinderService();


    }

    public function delete(int $id): void
    {
        $this->medicalFinderService->find($id);

        $appointments = $this->appointmentModel->findByMedicalId($id);
        
        if (!empty($appointments)) {
            throw new MedicalHasAppointmentsException(count($appointments));
        }

        $this->medicalModel->delete($id);


    }
}