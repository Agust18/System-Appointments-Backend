<?php
namespace App\Services\Patient;

use App\exception\Patient\PatientNotFoundException;
use App\Models\Appointment\AppointmentModel;
use App\Models\Patient\PatientModel;

final class PatientDeleterService
{
    private PatientModel $patientModel;
    private AppointmentModel $appointmentModel;

    public function __construct()
    {
        $this->patientModel = new PatientModel();
        $this->appointmentModel = new AppointmentModel();
    }

    public function delete(int $id):void
    {
        
        $patient = $this->patientModel->find($id); 
        if (!$patient) {
            throw new PatientNotFoundException($id);
        }
         $appointments = $this->appointmentModel->findByPatientId($id);
          if (!empty($appointments)) {
            $count = count($appointments);
            throw new \Exception("No se puede eliminar el paciente porque tiene {$count} turnos asignados");
        }
        
        $this->patientModel->delete($id);
      
        
    }
}