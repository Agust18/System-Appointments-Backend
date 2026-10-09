<?php

namespace App\Services\Patient;

use App\Exception\Patient\PatientNotFoundException;
use App\Models\Appointment\AppointmentModel;
use App\Models\Patient\PatientModel;
use App\Services\Patient\PatientFinderService;

final class PatientDeleterService
{
    private PatientModel $patientModel;
    private AppointmentModel $appointmentModel;
    private PatientFinderService $patientFinderService;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->appointmentModel = new AppointmentModel();
        $this->patientFinderService = new PatientFinderService();
    }

    public function delete(int $id): void
    {
        $this->patientFinderService->find($id);

        $appointments = $this->appointmentModel->findByPatientId($id);

        if (!empty($appointments)) {
            $count = count($appointments);
            throw new \Exception("No se puede eliminar el paciente porque tiene {$count} turnos asignados");
        }

        $this->patientModel->delete($id);
    }
}