<?php
namespace App\Services\Appointment\Projection;

use App\Converter\Appointment\Projection\AppointmentProjectionToAppointmentProjectionResponseConverter;
use App\Dto\Response\Appointment\Projection\AppointmentProjectionResponse;
use App\Entity\Appointment\Projection\AppointmentProjection;
use App\Exception\Appointment\AppointmentNotFoundException;
use App\Models\Appointment\AppointmentModel;

final class AppointmentProjectionFinderService
{
    private AppointmentModel $appointmentModel;
    private AppointmentProjectionToAppointmentProjectionResponseConverter $converter;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->converter = new AppointmentProjectionToAppointmentProjectionResponseConverter();
    }

    public function find(int $id): AppointmentProjection
    {
        $appointmentProjection = $this->appointmentModel->findProjection($id);
        
        if (!$appointmentProjection) {
            throw new AppointmentNotFoundException($id);
        }
        return $appointmentProjection;
      
    }

     public function findResponse(int $id): AppointmentProjectionResponse
    {
        $appointment = $this->find($id);
        return $this->converter->convert($appointment);
    }

    public function findByMedicalId(int $medicalId): array
    {
        $appointments = $this->appointmentModel->findByMedicalId($medicalId);
        $responses = [];
        foreach ($appointments as $appointment) {
            $responses[] = $this->converter->convert($appointment);
        }
        return $responses;
    }

    public function findByPatientId(int $patientId): array
    {
        $appointments = $this->appointmentModel->findByPatientId($patientId);
        $responses = [];
        foreach ($appointments as $appointment) {
            $responses[] = $this->converter->convert($appointment);
        }
        return $responses;
    }
}