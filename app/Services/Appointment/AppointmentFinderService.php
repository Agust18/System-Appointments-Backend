<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\AppointmentToAppointmentResponseConverter;
use App\Dto\Response\Appointment\AppointmentResponse;
use App\Entity\Appointment\Appointment;
use App\Exception\Appointment\AppointmentNotFoundException;
use App\Models\Appointment\AppointmentModel;

final class AppointmentFinderService
{
    private AppointmentModel $appointmentModel;
    private AppointmentToAppointmentResponseConverter $converter;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->converter = new AppointmentToAppointmentResponseConverter();
    }

    public function find(int $id): Appointment
    {
        $appointment = $this->appointmentModel->find($id);
        
        if (!$appointment) {
            throw new AppointmentNotFoundException($id);
        }
        return $appointment;
      
    }

     public function findResponse(int $id): AppointmentResponse
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