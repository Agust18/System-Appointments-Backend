<?php
namespace App\Controllers\Appointment;

use App\Services\Appointment\AppointmentFinderService;
use App\Services\Appointment\Projection\AppointmentProjectionFinderService;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

class AppointmentGetController extends ResourceController
{
    private AppointmentFinderService $appointmentFinderService;
    private AppointmentProjectionFinderService $appointmentProjectionFinderService;

    public function __construct()
    {
        $this->appointmentFinderService = new AppointmentFinderService();
        $this->appointmentProjectionFinderService = new AppointmentProjectionFinderService();
    }

    public function find(int $id): ResponseInterface
    {
        $appointmentResponse = $this->appointmentProjectionFinderService->findResponse($id);
        return $this->response->setJSON($appointmentResponse);
    }

    public function findByMedical(int $medicalId): ResponseInterface
    {
        $appointments = $this->appointmentFinderService->findByMedicalId($medicalId);
        return $this->response->setJSON($appointments);
    }

    public function findByPatient(int $patientId): ResponseInterface
    {
        $appointments = $this->appointmentFinderService->findByPatientId($patientId);
        return $this->response->setJSON($appointments);
    }
}