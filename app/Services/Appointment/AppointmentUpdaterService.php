<?php
namespace App\Services\Appointment;

use App\Converter\Appointment\AppointmentToAppointmentResponseConverter;
use App\Dto\Request\Appointment\AppointmentUpdateRequest;
use App\Dto\Response\Appointment\AppointmentResponse;
use App\exception\Appointment\AppointmentNotFoundException;
use App\Models\Appointment\AppointmentModel;
use App\Services\Appointment\AppointmentFinderService; 
use App\Services\User\UserFinderService;
use DateTime;


final class AppointmentUpdaterService
{
    private AppointmentModel $appointmentModel;
    private AppointmentToAppointmentResponseConverter $converter;
   private AppointmentFinderService $appointmentFinderService;
   private UserFinderService $userFinderService;

    public function __construct()
    {
        $this->appointmentModel = new AppointmentModel();
        $this->converter = new AppointmentToAppointmentResponseConverter();
        $this->appointmentFinderService = new AppointmentFinderService();
         $this->userFinderService = new UserFinderService();
    }

    public function update(AppointmentUpdateRequest $request, int $id): AppointmentResponse
    {
       
        $appointment = $this->appointmentFinderService->find($id);

        if (!$appointment) {
            throw new AppointmentNotFoundException($id);
        }

        if ($request->getFecha() && $request->getFecha() < date('Y-m-d')) {
            throw new \Exception('No se puede modificar un turno con fecha pasada');
        }

        $medicalId = $request->getMedicalId() ?? $appointment->getMedicalId();
        $fecha = $request->getFecha() ?? $appointment->getFecha()->format('Y-m-d');
        $hora = $request->getHora() ?? $appointment->getHora()->format('H:i:s');

        $existing = $this->appointmentModel->findByMedicalAndDateTime(
            $medicalId,
            $fecha,
            $hora
        );

        if ($existing && $existing->getId() !== $id) {
            throw new \Exception('Ya existe un turno para ese médico a esa hora');
        }

        
        $appointment->modify($request);
        
     
        $updatedAppointment = $this->appointmentModel->update($appointment);

        $response = $this->converter->convert($updatedAppointment);
        return $response;
        
       
    }
}