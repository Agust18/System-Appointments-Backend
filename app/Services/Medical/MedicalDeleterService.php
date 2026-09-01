<?php
namespace App\Services\Medical;

use App\exception\Medical\MedicalNotFoundException;
use App\Models\Appointment\AppointmentModel;
use App\Models\Medical\MedicalModel;

final class MedicalDeleterService
{
    private MedicalModel $medicalModel;
    private AppointmentModel $appointmentModel;
    public function __construct()
    {
        $this->medicalModel = new MedicalModel();
        $this->appointmentModel = new AppointmentModel();

    }

    public function delete(int $id): void
    {
        $medical = $this->medicalModel->find($id);

        if (!$medical) {
            throw new MedicalNotFoundException($id);
        }

         $appointments = $this->appointmentModel->findByMedicalId($id);
        if (!empty($appointments)) {
            $count = count($appointments);
            throw new \Exception("No se puede eliminar el médico porque tiene {$count} turnos asignados");
        }

        $this->medicalModel->delete($id);


    }
}